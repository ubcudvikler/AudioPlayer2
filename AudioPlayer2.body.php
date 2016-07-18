<?php
/**
 * AudioPlayer2 Class
 *
 * @ingroup Extensions
 * @author Ulrich Christensen
 * @version 1.1
 * @copyright © 2012 Ulrich Christensen
 * @licence GNU General Public Licence 2.0 or later
 */

/** AudioPlayer2 Class (static) mostly for code containment */
class AudioPlayer2 {
	private static $files = array();
	private static $width = 380; // Default width.
	private static $player_colours = array('loader' => '2B2B2B');
	private static $handled_tags = array('player'); // Default tags to handle
	
	/* List of handled mimetypes */
	private static $handled_mimetypes = array('audio/mpeg','audio/mp3');
	
	/* Add file to create player for */
	private static function addFile( $file, $id, $title = null, $artist = null ) {
		self::$files[] = array('file' => $file, 'id' => $id, 'title' => $title, 'artist' => $artist);
	}
	
	/* Make a unique id for the player instance to be placed into */
	private static function makeUniqueId() {
		static $uniq = 0;
		$uniq += 1;
		
		$id = 'audioplayer2_' . $uniq . '_' . mt_rand(1,1000000);
		
		return $id;
	}
	
	/** Creates the HTML code to display and adds file for loading via JavaScript
	 *
	 * @uses addFile
	 */
	public static function renderPlayerTag( $name, $args, $parser, $frame ) {
		$title = Title::makeTitleSafe(NS_IMAGE, $name);
		if (!$title) {
			return '<div class="error">' . wfMsg('audioplayer2-invalid-title') . '</div>';
		}

		$image = wfFindFile( $title );
		if ( !$image || !$image->exists() ) {
			return '<div class="error">' . wfMsg('audioplayer2-not-found') . '</div>';
		}

		$mimetype = $image->getMimeType();
		$mediatype = $image->getMediaType();

		if (!in_array($mimetype, self::$handled_mimetypes)) {
			// Display as page link if not applicable.
			return Linker::linkKnown($title);
		}

		$id = self::makeUniqueId();

		$file_title = null; // Use the title from ID3-tag.
		$file_author = null; // Use the artist from ID3-tag.
		
		if (isset($args['title'])) { // Overwrite title
			$file_title = htmlspecialchars($args['title']);
		}
		if (isset($args['artist'])) { // Overwrite artist
			$file_author = htmlspecialchars($args['artist']);
		}

		self::addFile($image, $id, $file_title, $file_author);

		return '<div id="'.$id.'">' . Linker::linkKnown($title) . '</div>';
	}
	
	/** Set the tags to handle (default: "player")
	 * @param array|string $tags - Either an array of tags (e.g. <code>array('player','play')</code>) or a single string tag (e.g. <code>'player'</code>).
	 */
	public static function setPlayerTags( $tags ) {
		if (!empty($tags)) {
			if (is_array($tags)) { // if array
				self::$handled_tags = $tags;
			} elseif (is_string($tags)) { // if string, make array
				self::$handled_tags = array($tags);
			}
		}
		// if empty or wrong type, do nothing.
	}
	
	/** Return array of the tags to handle */
	public static function getPlayerTags() {
		return self::$handled_tags;
	}

	/** Set the width of the players */
	public static function setWidth( $width ) {
		$new_width = @intval($width); // make sure width is integer
		
		if ($new_width > 0) {
			self::$width = $new_width;
		}
	}

	/** Set a certain hex color property of the player
	 * @param string $property - Name of the player element.
	 * @param string $hex - 6-digit Hexadecimal number (no prefixes: 0x or #)
	 */
	public static function setColour($property, $hex) {
		static $valid_colour_properties =
			array(
				'bg', 'leftbg', 'lefticon', 'voltrack',
				'volslider', 'rightbg', 'rightbghover',
				'righticon', 'righticonhover', 'loader',
				'track', 'tracker', 'border', 'skip', 'text'
			);
		
		if (in_array($property, $valid_colour_properties)) {
			if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
				self::$player_colours[$property] = $hex;
			} else {
				log_error('[AudioPlayer2] Incorrectly formatted hex "' . $hex . '"');
			}
		} else {
			log_error('[AudioPlayer2] Unhandled property "' . $property . '"');
		}
	}

	public static function setColours(array $colour_options) {
		foreach($colour_options as $k => $v) {
			self::setColour($k, $v);
		}
	}

	/** Support getting settings from global variable - $wgAudioPlayer2Settings */
	public static function initFromGlobals() {
		global $wgAudioPlayer2Settings;
		
		if (isset($wgAudioPlayer2Settings['width'])) {
			self::setWidth($wgAudioPlayer2Settings['width']);
		}
		if (isset($wgAudioPlayer2Settings['colours'])) {
			self::setColours($wgAudioPlayer2Settings['colours']);
		}
		if (isset($wgAudioPlayer2Settings['tags'])) {
			self::setPlayerTags($wgAudioPlayer2Settings['tags']);
		}
	}
	
	/** Output the JavaScript sections for loading the player */
	public static function output( $outputPage ) {
		global $wgJsMimeType, $wgAudioPlayerPluginPath;

		/* Include WP Audio Player v2.0 Script (and Flash player below during setup) */
		$outputPage->addScript(
			"<script type=\"{$wgJsMimeType}\" src=\"{$wgAudioPlayerPluginPath}/audio-player.js\">" .
			"</script>\n"
		);

		/* Setup player properties */
		$playerSetup = array();
		$playerSetup[] = '<script type="'.$wgJsMimeType.'">';
		$playerSetup[] = '	AudioPlayer.setup("'.$wgAudioPlayerPluginPath.'/player.swf", {';
		$playerSetup[] = '		width: ' . self::$width . ',';
		$playerSetup[] = '		transparentpagebg: "yes",';
		$playerSetup[] = '		autostart: "no",';
		// $playerSetup[] = '		initialvolume: 60,'; # default value
		foreach(self::$player_colours as $k => $v) {
			$playerSetup[] = '		' . $k . ': "' . $v . '",';
		}
		$playerSetup[] = '	});';
		$playerSetup[] = '';
		$playerSetup[] = '	audioplayer2_loadPlayers();';
		$playerSetup[] = '</script>' . "\n";
		
		$outputPage->addScript(implode("\n", $playerSetup));
		
		return true;
	}

	public static function parserDone( $parser, &$text ) {
		global $wgJsMimeType;
		
		if (!empty(self::$files)) {
			/* Make embedding */
			
			$filescript = array();
			$filescript[] = "\n<script type=\"{$wgJsMimeType}\">";
			
			$filescript[] = '	function audioplayer2_loadPlayers() {'; // Executed when AudioPlayer is loaded.
			
			foreach(self::$files as $key => $file) {
					$image = $file['file'];

					/* append embed code */
					
					$filescript[] = '		AudioPlayer.embed("' . $file['id'] . '", {';
					$filescript[] = '			soundFile: "' . htmlspecialchars($image->getURL()) . '",';
					if (!empty($file['title'])) { // Set overwrite title
					$filescript[] = '			titles: "' . $file['title'] . '",';
					}
					if (!empty($file['artist'])) { // Set overwrite artist
					$filescript[] = '			artists: "'.$file['artist'].'",';
					}
					$filescript[] = '		});';
			}
			
			$filescript[] = '	}'; // end function
			$filescript[] = "</script>\n";
			
			$text .= implode("\n", $filescript);
			
			$parser->mOutput->mAudioPlayer2 = true; // An indicator to tell that the player should be loaded.
			//$parser->mOutput->setFlag('AudioPlayer2');
			
			self::$files = array(); // Ensure that it is not processed multiple times.
		}
		return true;
	}	
}
