<?php
/**
 * A simple example script for calling the Slack API.
 *
 * This example saves the access token and related information in a simple
 * text file in the same directory with the script. Please notice that this
 * approach is for demonstration purposes only and not suited for real
 * applications.
 *
 * @author Jarkko Laine <jarkko@jarkkolaine.com>
 */

// Define Slack application identifiers
// Even better is to put these in environment variables so you don't risk exposing
// them to the outer world (e.g. by committing to version control)
define( 'SLACK_CLIENT_ID', '50306003538.50342651873' );
define( 'SLACK_CLIENT_SECRET', 'aba89cf332a5f98916340aad4a60f649' );
define( 'SLACK_COMMAND_TOKEN', 'CRoKCU38gFzmPV5O8bVA4JQc' );

// For using libraries through Composer
require_once 'vendor/autoload.php';

// Include our Slack interface classes
require_once 'slack-interface/class-slack.php';
require_once 'slack-interface/class-slack-access.php';
require_once 'slack-interface/class-slack-api-exception.php';

use Slack_Interface\Slack;
use Slack_Interface\Slack_API_Exception;

//
// HELPER FUNCTIONS
//

/**
 * Initializes the Slack handler object, loading the authentication
 * information from a text file. If the text file is not present,
 * the Slack handler is initialized in a non-authenticated state.
 *
 * @return Slack    The Slack interface object
 */
function initialize_slack_interface() {
	// Read the access data from a text file
	if ( file_exists( 'access.txt' ) ) {
		$access_string = file_get_contents( 'access.txt' );
	} else {
		$access_string = '{}';
	}

	// Decode the access data into a parameter array
	$access_data = json_decode( $access_string, true );

	$slack = new Slack( $access_data );

	// Register slash commands
	$slack->register_slash_command( '/dirty_bitches', 'slack_command_joke' );

	return $slack;
}

/**
 * Executes an application action (e.g. 'send_notification').
 *
 * @param Slack  $slack     The Slack interface object
 * @param string $action    The id of the action to execute
 *
 * @return string   A result message to show to the user
 */
function do_action( $slack, $action ) {
	$result_message = '';

	switch ( $action ) {

		// Handles the OAuth callback by exchanging the access code to
		// a valid token and saving it in a file
		case 'oauth':
			$code = $_GET['code'];

			// Exchange code to valid access token
			try {
				$access = $slack->do_oauth( $code );
				if ( $access ) {
					file_put_contents( 'access.txt', $access->to_json() );
					$result_message = 'The application was successfully added to your Slack channel';
				}
			} catch ( Slack_API_Exception $e ) {
				$result_message = $e->getMessage();
			}
			break;

		// Sends a notification to Slack
		case 'send_notification':
			$message = isset( $_REQUEST['text'] ) ? $_REQUEST['text'] : 'Hello!';

			try {
				$slack->send_notification( $message );
				$result_message = 'Notification sent to Slack channel.';
			} catch ( Slack_API_Exception $e ) {
				$result_message = $e->getMessage();
			}
			break;

		// Responds to a Slack slash command. Notice that commands are registered
		// at Slack initialization.
		case 'command':
			$slack->do_slash_command();
			break;

		default:
			break;

	}

	return $result_message;
}

/**
 * A simple slash command that returns a random joke to the Slack channel.
 *
 * @return array        A data array to return to Slack
 */
function slack_command_joke() {
	$bitches = [
		'http://i.giphy.com/Ed3Jpty9JPnPO.gif',
		'http://i.giphy.com/3o7abAsUDj5cOzuCJ2.gif',
		'http://i.giphy.com/9n9TI4yi4EkXC.gif',
		'http://i.giphy.com/xTk9ZLpqvjCb8JG1nG.gif',
		'http://i.giphy.com/o4yzmqAp9wuBy.gif',
		'http://i.giphy.com/K0Muoyvf8GSJO.gif',
		'http://i.giphy.com/qfsmPduiv9Uju.gif',
		'http://i.giphy.com/AsW6f24WSrG8w.gif',
		'http://i.giphy.com/B0UnR4nmWMFpK.gif',
		'http://i.giphy.com/GgyY6X9wk2dsk.gif',
		'http://i.giphy.com/i17L5UDJugaCA.gif',
		'http://i.giphy.com/Mc5ddN78OlTmo.gif',
		'http://i.giphy.com/slhay2qwQCiWs.gif',
		'http://i.giphy.com/OXJUIgxaX0loI.gif',
		'http://i.giphy.com/yjf25nyKCbB4I.gif',
		'http://i.giphy.com/w95g1K9Lu0guY.gif',
		'http://i.giphy.com/PpMaW39IQzNfO.gif',
		'http://i.giphy.com/t733NMVDCvB6M.gif',
		'http://i.giphy.com/uOYwaO5HlLTyM.gif',
		'http://i.giphy.com/tHWJUanyL2xS8.gif',
		'http://i.giphy.com/qGiVGk6i3ulpu.gif',
		'http://i.giphy.com/x4evbMlhpVNCw.gif',
		'http://i.giphy.com/t9x121JPbkEc8.gif',
	];

	if (empty($_POST['text']) || $_POST['text'] != 'please') {
		$message = 'What\'s the magic word?';
	} else {
		$message = $bitches[mt_rand(0, count($bitches) - 1)];
	}

	return array(
		'response_type' => 'in_channel',
		'text' => $message,
	);
}

//
// MAIN FUNCTIONALITY
//

// Setup the Slack handler
$slack = initialize_slack_interface();

// If an action was passed, execute it before rendering the page layout
$result_message = '';
if ( isset( $_REQUEST['action'] ) ) {
	$action = $_REQUEST['action'];
	$result_message = do_action( $slack, $action );
}

//
// PAGE LAYOUT
//

?>
<html>
	<head>
		<title>Slack Integration Example</title>

		<style>
			body {
				font-family: Helvetica, sans-serif;
				padding: 20px;
			}

			.notification {
				padding: 20px;
				background-color: #fafad2;
			}

			input {
				padding: 10px;
				font-size: 1.2em;
				width: 100%;
			}
		</style>
	</head>

	<body>
		<h1>Slack Integration Example</h1>

		<?php if ( $result_message ) { ?>
			<p class="notification">
				<?php echo $result_message; ?>
			</p>
		<?php } ?>

		<?php if ( $slack->is_authenticated() ) { ?>
			<form action="" method="post">
				<input type="hidden" name="action" value="send_notification"/>
				<p>
					<input type="text" name="text" placeholder="Type your notification here and press enter to send." />
				</p>
			</form>
		<?php } else { ?>
			<p>
				<a href="https://slack.com/oauth/authorize?scope=incoming-webhook,commands&client_id=<?php echo $slack->get_client_id(); ?>"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x"></a>
			</p>
		<?php } ?>

	</body>
</html>
