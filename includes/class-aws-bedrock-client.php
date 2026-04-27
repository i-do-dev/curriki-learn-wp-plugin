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

	const REGION = 'us-east-1';

	/**
	 * Primary model identifier — global inference profile for Claude Sonnet 4.6.
	 * Claude 4.x models use a bare provider/name string with no date suffix or `:0`.
	 * Source: https://platform.claude.com/docs/en/docs/about-claude/models/all-models
	 */
	const MODEL_ID = 'anthropic.claude-sonnet-4-6';

	/**
	 * Cross-region US inference profile for Claude Sonnet 4.6.
	 * AWS dynamically routes requests across us-east-1 / us-east-2 / us-west-2 for
	 * higher availability.  Some account or IAM configurations require this format
	 * rather than the bare global model ID above.
	 * Used automatically as a fallback when the primary ID is rejected.
	 */
	const MODEL_ID_CROSS_REGION = 'us.anthropic.claude-sonnet-4-6-v1:0';

	/**
	 * WordPress option key that persists whichever model ID was confirmed to work
	 * at runtime.  Overrides both constants above so future calls skip auto-detect.
	 * Can also be set manually: update_option( 'tl_bedrock_model_id', 'custom-id' );
	 */
	const OPTION_MODEL_ID = 'tl_bedrock_model_id';

	/** @var BedrockRuntimeClient|null  Reused across calls in the same request. */
	private static $client = null;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Invoke the Bedrock Converse API and return the text response.
	 *
	 * Automatically detects the correct model ID for this AWS account:
	 *   1. Uses the model ID stored in the WP option (if set).
	 *   2. Falls back to MODEL_ID (global inference profile).
	 *   3. On "invalid model identifier" error, retries with MODEL_ID_CROSS_REGION.
	 *   4. If the cross-region ID succeeds, persists it to the WP option so all
	 *      subsequent requests go directly there without a retry round-trip.
	 *
	 * @param  string          $user_message   Content of the user turn.
	 * @param  string          $system_prompt  Optional system-level instruction.
	 * @return string|WP_Error                 Generated text or WP_Error on failure.
	 */
	public static function invoke_bedrock( $user_message, $system_prompt = '' ) {
		$model_id = self::get_model_id();
		$result   = self::call_converse( $model_id, $user_message, $system_prompt );

		// If the chosen model ID was rejected as invalid, automatically retry with
		// the cross-region inference profile — unless we already used it.
		if ( is_wp_error( $result )
			&& 'bedrock_invalid_model_id' === $result->get_error_code()
			&& $model_id !== self::MODEL_ID_CROSS_REGION
		) {
			$fallback = self::call_converse( self::MODEL_ID_CROSS_REGION, $user_message, $system_prompt );

			if ( ! is_wp_error( $fallback ) ) {
				// Cross-region ID works — persist it so all future calls skip the retry.
				update_option( self::OPTION_MODEL_ID, self::MODEL_ID_CROSS_REGION );
				return $fallback;
			}

			// Both IDs rejected — return a combined, actionable error message.
			return new WP_Error(
				'bedrock_invalid_model_id',
				$result->get_error_message()
				. ' Fallback cross-region inference profile "' . self::MODEL_ID_CROSS_REGION
				. '" was also attempted and failed. '
				. 'Go to AWS Console → Bedrock → Model Access and enable "Claude Sonnet 4.6" '
				. 'for region "' . self::REGION . '".',
				array( 'tried_ids' => array( $model_id, self::MODEL_ID_CROSS_REGION ) )
			);
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Internal
	// -------------------------------------------------------------------------

	/**
	 * Return the model ID to use for the current call.
	 *
	 * Priority: WP option override → MODEL_ID constant.
	 *
	 * @return string
	 */
	private static function get_model_id() {
		$option = get_option( self::OPTION_MODEL_ID, '' );
		return ( is_string( $option ) && '' !== $option ) ? $option : self::MODEL_ID;
	}

	/**
	 * Send a single Converse API request with the given model ID.
	 *
	 * All exception handling lives here.  invoke_bedrock() uses this method to
	 * implement the primary-call + cross-region-fallback retry pattern.
	 *
	 * @param  string          $model_id       Bedrock model or inference-profile ID.
	 * @param  string          $user_message   Content of the user turn.
	 * @param  string          $system_prompt  Optional system-level instruction.
	 * @return string|WP_Error                 Generated text or WP_Error on failure.
	 */
	private static function call_converse( $model_id, $user_message, $system_prompt ) {
		try {
			$params = array(
				'modelId'  => $model_id,
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
					$message = 'Access denied to the Bedrock model "' . $model_id . '". '
						. 'Verify that: (1) the EC2 IAM role has the "bedrock:InvokeModel" '
						. 'permission for this model ARN, and (2) the model is enabled under '
						. 'AWS Console → Bedrock → Model Access for region "' . self::REGION . '". '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_access_denied', $message );

				case 'ResourceNotFoundException':
					// The model ID string does not map to any known model.
					$message = 'The Bedrock model "' . $model_id . '" was not found in '
						. 'region "' . self::REGION . '". '
						. 'Check TL_AWS_Bedrock_Client::MODEL_ID or the "' . self::OPTION_MODEL_ID . '" '
						. 'WP option, and confirm the model is available in this region. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_model_not_found', $message );

				case 'ValidationException':
					// Distinguish "invalid model identifier" from other validation errors.
					// The former is retryable with a different model ID format; the latter
					// indicates a malformed request payload and is not retryable.
					if ( false !== stripos( $aws_msg, 'model identifier' ) ) {
						$message = 'The Bedrock model identifier "' . $model_id . '" is invalid '
							. 'or not accessible in region "' . self::REGION . '". '
							. 'Valid formats for Claude Sonnet 4.6 are: '
							. '"' . self::MODEL_ID . '" (global) or '
							. '"' . self::MODEL_ID_CROSS_REGION . '" (cross-region US). '
							. 'Ensure the model is enabled under '
							. 'AWS Console → Bedrock → Model Access for region "' . self::REGION . '". '
							. 'To override the model ID without a code change, set the '
							. '"' . self::OPTION_MODEL_ID . '" WordPress option. '
							. 'AWS detail: ' . $aws_msg;
						return new WP_Error( 'bedrock_invalid_model_id', $message );
					}

					// Non-model-ID validation failure — malformed request payload.
					$message = 'The request sent to Bedrock was invalid (model: "' . $model_id . '"). '
						. 'This may indicate a mismatch between the Converse API parameters '
						. 'and the model version. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_validation_error', $message );

				case 'ThrottlingException':
					// Request rate exceeded the account quota for this model.
					$message = 'AWS Bedrock throttled the request for model "' . $model_id . '". '
						. 'The account has exceeded its request quota. '
						. 'Retry after a short delay or request a quota increase in the AWS console. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_throttled', $message );

				case 'ModelTimeoutException':
					// The model took too long to generate a response.
					$message = 'The Bedrock model "' . $model_id . '" timed out while generating a response. '
						. 'The lesson content may be too long. Try shortening the input. '
						. 'AWS detail: ' . $aws_msg;
					return new WP_Error( 'bedrock_model_timeout', $message );

				case 'ModelNotReadyException':
					// The model is being provisioned or is temporarily unavailable.
					$message = 'The Bedrock model "' . $model_id . '" is not ready. '
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

