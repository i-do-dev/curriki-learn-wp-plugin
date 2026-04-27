<?php

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

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

		} catch ( AwsException $e ) {
			return new WP_Error( 'bedrock_aws_error', 'AWS Error: ' . $e->getAwsErrorMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'bedrock_error', 'Error: ' . $e->getMessage() );
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

