<?php

namespace Give\MultiFormGoals\MultiFormGoal;

use Give\MultiFormGoals\ProgressBar\Model as ProgressBar;

class Model {

	// Settings for shortcode context
	protected $ids;
	protected $tags;
	protected $categories;
	protected $metric;
	protected $goal;
	protected $color;

	// Settings for block context
	protected $innerBlocks;

	/**
	 * Constructs and sets up setting variables for a new Multi Form Goal model
	 *
	 * @param array $args Arguments for new Multi Form Goal, including 'ids'
	 * @since 2.9.0
	 **/
	public function __construct( array $args ) {
		isset( $args['ids'] ) ? $this->ids                 = $args['ids'] : $this->ids = [];
		isset( $args['tags'] ) ? $this->tags               = $args['tags'] : $this->tags = [];
		isset( $args['categories'] ) ? $this->categories   = $args['categories'] : $this->categories = [];
		isset( $args['metric'] ) ? $this->metric           = $args['metric'] : $this->metric = 'revenue';
		isset( $args['goal'] ) ? $this->goal               = $args['goal'] : $this->goal = '1000';
		isset( $args['color'] ) ? $this->color             = $args['color'] : $this->color = '#28c77b';
		isset( $args['innerBlocks'] ) ? $this->innerBlocks = $args['innerBlocks'] : $this->innerBlocks = false;
	}

	/**
	 * Get output markup for Totals
	 *
	 * @return string
	 * @since 2.9.0
	 **/
	public function getOutput() {
		ob_start();
		$output = '';
		require $this->getTemplatePath();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Get Progress Bar output
	 *
	 * @return string
	 * @since 2.9.0
	 **/
	protected function getProgressBarOutput() {
		$progressBar = new ProgressBar(
			[
				'ids'        => $this->ids,
				'tags'       => $this->tags,
				'categories' => $this->categories,
				'metric'     => $this->metric,
				'goal'       => $this->goal,
				'color'      => $this->color,
			]
		);
		return $progressBar->getOutput();
	}

	/**
	 * Get template path for Totals component template
	 * @since 2.9.0
	 **/
	public function getTemplatePath() {
		return GIVE_PLUGIN_DIR . '/src/MultiFormGoals/resources/views/multiformgoal.php';
	}

}
