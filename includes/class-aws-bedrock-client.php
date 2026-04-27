<?php

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Exception\ConnectException;

/**
 * AWS Bedrock client — wraps the AWS SDK BedrockRuntimeClient.
 *
 * Credential resolution is handled automatically by the SDK using the
 * EC2 Instance Metadata Service (IMDSv2). No keys need to be configured
 * manually when the instance has an IAM role with Bedrock access attached.
 */
class TL_AWS_Bedrock_Client {

	const REGION   = 'us-east-1';
	const MODEL_ID = 'anthropic.claude-sonnet-4-6:0';

	/** @var BedrockRuntimeClient|null  Reused across calls in the same request. */
	private static $client = null;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Invoke the Bedrock Converse API and return the text response.
	 *
	 * @param  string          $user_message   Content of the user turn.
	 * @param  string          $system_prompt  Optional system-level instruction.
	 * @return string|WP_Error                 Generated text or WP_Error on failure.
	 */
	public static function invoke_bedrock( $user_message, $system_prompt = '' ) {
		try {
			$params = array(
				'modelId'  => self::MODEL_ID,
				'messages' => array(
					array(
						'role'    => 'user',
						'content' => array( array( 'text' => $user_message ) ),
					),
				),
				'inferenceConfig' => array(
					'maxTokens'   => 4096,
					'temperature' => 0.3,
				),
			);

			if ( ! empty( $system_prompt ) ) {
				$params['system'] = array( array( 'text' => $system_prompt ) );
			}

			$result = self::client()->converse( $params );

			return $result['output']['message']['content'][0]['text'];

		} catch ( CredentialsException $e ) {
			// The EC2 instance metadata service (IMDSv2) could not supply credentials.
			// This means either: (a) not running on EC2, (b) no IAM role attached to
			// the instance, or (c) the IMDS endpoint is blocked / not reachable.
			return new WP_Error(
				'bedrock_credentials_error',
				'AWS credentials could not be resolved. '
				. 'Ensure this server is an EC2 instance with an IAM role that has '
				. '"bedrock:InvokeModel" permission attached, and that the Instance '
				. 'Metadata Service (IMDSv2) is accessible. '
				. 'Detail: ' . $e->getMessage()
			);

		} catch ( ConnectException $e ) {
			// The HTTP client could not establish a TCP connection to the Bedrock
			// regional endpoint. Typical causes: VPC missing a route to the internet
			// or a Bedrock VPC endpoint, a security-group / NACL blocking outbound
			// HTTPS (port 443), or a DNS resolution failure for the endpoint.
			return new WP_Error(
				'bedrock_connect_error',
				'Cannot reach the AWS Bedrock service endpoint ('
				. 'bedrock-runtime.' . self::REGION . '.amazonaws.com). '
				. 'Check that the EC2 instance has outbound HTTPS access to AWS Bedrock '
				. '(internet gateway, NAT gateway, or a Bedrock VPC endpoint) and that '
				. 'security groups / NACLs allow port 443. '
				. 'Detail: ' . $e->getMessage()
			);

		} catch ( AwsException $e ) {
			$error_code = $e->getAwsErrorCode();
			$aws_msg    = $e->getAwsErrorMessage();

			switch ( $error_code ) {

				case 'AccessDeniedException':
					// The IAM role exists but lacks bedrock:InvokeModel on this model,
					// OR the model has not been enabled in Bedrock Model Access settings.
					$message = 'Access denied to the Bedrock model "' . self::MODEL_ID . '". '
						. 'Verify that: (1) the EC2 IAM role has the "bedrock:InvokeModel" '
						. 'permission for this model ARN, and (2) the model is enabled under '
						. 'AWS Console → Bedrock → Model Access for region "' . self::REGION . '". '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_access_denied', $message );

				case 'ResourceNotFoundException':
					// The model ID string does not map to any known model.
					$message = 'The Bedrock model "' . self::MODEL_ID . '" was not found in '
						. 'region "' . self::REGION . '". '
						. 'Check the MODEL_ID constant in TL_AWS_Bedrock_Client and confirm '
						. 'the model is available in this region. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_model_not_found', $message );

				case 'ValidationException':
					// The request payload was malformed or contained an unsupported parameter.
					$message = 'The request sent to Bedrock was invalid. '
						. 'This may indicate a mismatch between the Converse API parameters '
						. 'and the model version. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_validation_error', $message );

				case 'ThrottlingException':
					// Request rate exceeded the account quota for this model.
					$message = 'AWS Bedrock throttled the request. '
						. 'The account has exceeded its request quota for model "' . self::MODEL_ID . '". '
						. 'Retry after a short delay or request a quota increase in the AWS console. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_throttled', $message );

				case 'ModelTimeoutException':
					// The model took too long to generate a response.
					$message = 'The Bedrock model timed out while generating a response. '
						. 'The lesson content may be too long. Try shortening the input. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_model_timeout', $message );

				case 'ModelNotReadyException':
					// The model is being provisioned or is temporarily unavailable.
					$message = 'The Bedrock model "' . self::MODEL_ID . '" is not ready. '
						. 'It may still be provisioning or is temporarily unavailable. '
						. 'Retry in a few minutes. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_model_not_ready', $message );

				case 'ServiceUnavailableException':
					$message = 'The AWS Bedrock service is temporarily unavailable in region "'
						. self::REGION . '". Retry after a few minutes. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_service_unavailable', $message );

				default:
					// Catch-all for any other AWS-level error (e.g. InternalServerError).
					return new WP_Error(
						'bedrock_aws_error',
						'AWS Bedrock error [' . $error_code . ']: ' . $aws_msg
					);
			}

		} catch ( Exception $e ) {
			// Non-AWS exception: unexpected runtime error (e.g. SDK version mismatch,
			// malformed response, PHP environment issue).
			return new WP_Error(
				'bedrock_error',
				'Unexpected error while calling AWS Bedrock: ' . $e->getMessage()
			);
		}
	}

	// -------------------------------------------------------------------------
	// Internal
	// -------------------------------------------------------------------------

	/**
	 * Return a shared BedrockRuntimeClient instance.
	 * The SDK resolves credentials automatically from the EC2 IAM role.
	 *
	 * @return BedrockRuntimeClient
	 */
	private static function client() {
		if ( is_null( self::$client ) ) {
			self::$client = new BedrockRuntimeClient(
				array(
					'region'  => self::REGION,
					'version' => 'latest',
				)
			);
		}

		return self::$client;
	}
}

