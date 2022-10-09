<?php
/*
To to list:
	- Inline styles
		- [x] <img ... loading="lazy" />
		- [x] Emoji Code   -  :xd:    ->  <img class="emoji" alt=":xd:" src="$baseEmojiPath/xd.png" height="20" loading="lazy">
		- [x] Spoiler Text - ||BE||   ->  <del class="spoiler">BE</del>  ( discord flavor.  meanwhile >!reddit flavor spoiler!< )
		- [ ] Kaomoji a11y -   o_o    ->  <span role="img">o_o</span>    ( or + aria-label="kaomoji" )
		- [ ] <small>
		- [ ] <cite>
		- [ ] bracketed spans: [Red text]{.red}
		- [x] split <i> from <em>, <b> from <strong>

	- [ ] Modify Parsedown.php:unmarkedText()  ???
		# prevent HTML <element>\n from <br>ed   [^>]
		if ($this->breaksEnabled)
			$text = preg_replace('/[ ]*\n/', "<br />\n", $text);
*/

class ParsedownAA extends ParsedownExtra {
	use ParsedownExt;
}



trait ParsedownExt {

	function __construct( )
	{
		if ( method_exists( get_parent_class($this), '__construct') )
			parent::__construct();

		$this->InlineTypes[':'][]= 'EmojiCode';
		$this->InlineTypes['|'][]= 'Spoiler';
	//	$this->inlineMarkerList = implode ('', array_keys($this->InlineTypes));
	//	$this->inlineMarkerList .= '|';
		$this->inlineMarkerList = '!"*_&[:<>`|~\\';

		$this->StrongRegex = array(
			'*' => array('/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s','strong'),
		//	'*' => array('/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s','strong'), // 1.8.x
			'_' => array('/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us','b'),
		//	'_' => array('/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us','b'),                   // 1.8.x
		);
		$this->EmRegex = array(
			'*' => array('/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s','em'),
			'_' => array('/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us','i'),
		);

	}

	function setBaseEmojiPath($baseEmojiPath){
		 $this->baseEmojiPath=$baseEmojiPath;
		return $this;
	}
	protected $baseEmojiPath = '';

	function setBaseImagePath($baseImagePath){
		 $this->baseImagePath=$baseImagePath;
		return $this;
	}
	protected $baseImagePath = '';

	# custom emoji code
	protected function inlineEmojiCode($excerpt)
	{
		if (preg_match('/^:([\w_\-]{1,20}):(?!=:)/i', $excerpt['text'], $matches))
		{
			return array(
				// How many characters to advance the Parsedown's cursor after being done processing this tag.
				'extent' => strlen($matches[0]), 
				'element' => [
					'name' => 'img',
					'attributes' => [
						'src'    => $this->baseEmojiPath . strtolower($matches[1]) . '.png',
						'class'  => 'emoji',
						'alt'    => ':' . $matches[1] . ':',
						'height' => '20',
						'loading'=> 'lazy',
					],
				],
			);
		}
	}

	# Spoiler Text
	protected function inlineSpoiler($excerpt)
	{
		if ( !isset($excerpt['text'][1]) )
			return;
		if ($excerpt['text'][1] === '|' 
		and preg_match('/^[|]{2}(?=\S)(.+?)(?<=\S)[|]{2}/', $excerpt['text'], $matches))
		{
			return array(
				'extent' => strlen($matches[0]), 
				'element' => [
					'name' => 'del',
					'text'   => $matches[1],
					'handler' => 'line',
					'attributes' => [
						'class'  => 'spoiler',
					],
				],
			);
		}
	}


	# override: _X_ as <i>, __X__ as <b>
	protected function inlineEmphasis($Excerpt)
	{
		if ( ! isset($Excerpt['text'][1]))
			return;

		$marker = $Excerpt['text'][0];

		if ($Excerpt['text'][1] === $marker 
			&&  preg_match($this->StrongRegex[$marker][0], $Excerpt['text'], $matches))
			$emphasis	 = $this->StrongRegex[$marker][1];
		elseif (preg_match($this->EmRegex[$marker][0],	 $Excerpt['text'], $matches))
			$emphasis	 = $this->EmRegex[$marker][1];
		else
			return;

		return array(
			'extent' => strlen($matches[0]),
			'element' => array(
				'name' => $emphasis,
				'handler' => 'line',
				'text' => $matches[1],
			),
		);
	}

	# <img>: attribute: `loading=lazy`, ![](x.png) -> .../
	protected function inlineImage($excerpt)
	{
		$image = parent::inlineImage($excerpt);
		if ( ! isset($image) ) { return null; }

		$image['element']['attributes']['loading'] = 'lazy';

		$src = $image['element']['attributes']['src'];
		if ( '/' !== $src[0] && ':' !== $src[0] && ':' !== $src[4] && ':' !== $src[5] ) {
			$image['element']['attributes']['src'] = $this->baseImagePath . $src;
		}

		return $image;
	}

	/*
	*/
}

