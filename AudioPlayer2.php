<?php
/**
 * AudioPlayer2 extension - Audio playback using WordPress Audio Player: Standalone version
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once( $IP . '/extensions/AudioPlayer2/AudioPlayer2.php');
 *
 * @ingroup Extensions
 * @author Ulrich Christensen
 * @version 1.1
 * @copyright © 2012 Ulrich Christensen
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'AudioPlayer2',
	'version' => '1.1',
	'author' => 'Ulrich Christensen',
	'url' => 'https://github.com/ubcudvikler/AudioPlayer2',
	'descriptionmsg' => 'audioplayer2-desc',
);

/* --- Default configuration options --- */

/* Specify the directory, where WordPress Audio Player is located: */
$wgAudioPlayerPluginPath = '/extensions/AudioPlayer2/audio-player';
/* As a default, assume the directory is placed within this extension's default
 * directory, but as this is not necessarily ideal, it is recommended that
 * this is explicitly set.
 */

/* For specifying settings global variable style */
$wgAudioPlayer2Settings = array();

/* --- Setup --- */

// Setup the extension to detect occurrences of the handled tags on parser start-up
$wgHooks['ParserFirstCallInit'][] = 'audioplayer2_setup';

// Include JavaScript for player instances at the latest possible moment of parsing.
$wgHooks['ParserAfterTidy'][] = 'audioplayer2_postParsing';

// Include and setup the player plugin on the final page if any players were found.
$wgHooks['OutputPageParserOutput'][] = 'audioplayer2_ParserOutput';

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['AudioPlayer2'] = $dir . 'AudioPlayer2.i18n.php';

$wgAutoloadClasses['AudioPlayer2'] = $dir . 'AudioPlayer2.body.php';

$wgExtensionFunctions[] = array('AudioPlayer2','initFromGlobals');


/* --- Hook functions --- */

/* Set hook for player */
function audioplayer2_setup( $parser ) {
	$tags = AudioPlayer2::getPlayerTags();
	foreach ($tags as $tag) {
		$parser->setHook( $tag, array('AudioPlayer2', 'renderPlayerTag' ));
	}
	return true;
}

/* Write embedding scripts for the player instances */
function audioplayer2_postParsing( $parser, &$text ) {
	return AudioPlayer2::parserDone( $parser, $text );
}

function audioplayer2_ParserOutput( $outputPage, $parserOutput )  {
	if (isset($parserOutput->mAudioPlayer2) /*$parserOutput->getFlag('AudioPlayer2')*/) {
		AudioPlayer2::output( $outputPage );
	}
	return true;
}