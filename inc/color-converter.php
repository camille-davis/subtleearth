<?php
/**
 * Utilities to convert hex colors to RGB, or CSS filters.
 *
 * @package Subtle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color converter class.
 */
class Subtle_Color_Converter {

	/**
	 * Convert hex color to RGB array.
	 *
	 * @param string $hex Hex color code.
	 * @return array RGB values as array with 'r', 'g', 'b' keys.
	 */
	public static function hex_to_rgb( $hex ) {

		// Remove hash if present.
		$hex = ltrim( $hex, '#' );

		// Expand shorthand hex (e.g., "03F" to "0033FF").
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Convert hex color to CSS filter string.
	 *
	 * @param string $hex Hex color code.
	 * @return string CSS filter string.
	 */
	public static function hex_to_css_filter( $hex ) {
		$rgb = self::hex_to_rgb( $hex );

		// Create color object and solve for filter.
		$color  = new FilterColor( $rgb['r'], $rgb['g'], $rgb['b'] );
		$solver = new FilterSolver( $color );
		$result = $solver->solve();

		return $result['filter'];
	}
}

/**
 * Low-level color representation for CSS filter calculations.
 */
class FilterColor {

	/**
	 * Red component (0-255).
	 *
	 * @var int
	 */
	public $r;

	/**
	 * Green component (0-255).
	 *
	 * @var int
	 */
	public $g;

	/**
	 * Blue component (0-255).
	 *
	 * @var int
	 */
	public $b;

	/**
	 * Constructor.
	 *
	 * @param int $r Red component (0-255).
	 * @param int $g Green component (0-255).
	 * @param int $b Blue component (0-255).
	 */
	public function __construct( $r, $g, $b ) {
		$this->set( $r, $g, $b );
	}

	/**
	 * Set color values.
	 *
	 * @param int $r Red component (0-255).
	 * @param int $g Green component (0-255).
	 * @param int $b Blue component (0-255).
	 */
	public function set( $r, $g, $b ) {
		$this->r = $this->clamp( $r );
		$this->g = $this->clamp( $g );
		$this->b = $this->clamp( $b );
	}

	/**
	 * Clamp value between 0 and 255.
	 *
	 * @param int|float $value Value to clamp.
	 * @return int Clamped value.
	 */
	public function clamp( $value ) {
		return max( 0, min( 255, $value ) );
	}

	/**
	 * Apply linear transformation.
	 *
	 * @param float $slope Slope value.
	 * @param float $intercept Intercept value.
	 */
	public function linear( $slope = 1, $intercept = 0 ) {
		$this->r = $this->clamp( $this->r * $slope + $intercept * 255 );
		$this->g = $this->clamp( $this->g * $slope + $intercept * 255 );
		$this->b = $this->clamp( $this->b * $slope + $intercept * 255 );
	}

	/**
	 * Apply matrix multiplication.
	 *
	 * @param array $matrix 3x3 transformation matrix.
	 */
	public function multiply( $matrix ) {
		$new_r = $this->clamp(
			$this->r * $matrix[0] + $this->g * $matrix[1] + $this->b * $matrix[2]
		);
		$new_g = $this->clamp(
			$this->r * $matrix[3] + $this->g * $matrix[4] + $this->b * $matrix[5]
		);
		$new_b = $this->clamp(
			$this->r * $matrix[6] + $this->g * $matrix[7] + $this->b * $matrix[8]
		);

		$this->r = $new_r;
		$this->g = $new_g;
		$this->b = $new_b;
	}

	/**
	 * Apply invert filter.
	 *
	 * @param float $value Invert value (0-1).
	 */
	public function invert( $value = 1 ) {
		$this->r = ( $value + ( $this->r / 255 ) * ( 1 - 2 * $value ) ) * 255;
		$this->g = ( $value + ( $this->g / 255 ) * ( 1 - 2 * $value ) ) * 255;
		$this->b = ( $value + ( $this->b / 255 ) * ( 1 - 2 * $value ) ) * 255;
	}

	/**
	 * Apply sepia filter.
	 *
	 * @param float $value Sepia value (0-1).
	 */
	public function sepia( $value = 1 ) {
		$this->multiply(
			array(
				0.393 + 0.607 * ( 1 - $value ),
				0.769 - 0.769 * ( 1 - $value ),
				0.189 - 0.189 * ( 1 - $value ),
				0.349 - 0.349 * ( 1 - $value ),
				0.686 + 0.314 * ( 1 - $value ),
				0.168 - 0.168 * ( 1 - $value ),
				0.272 - 0.272 * ( 1 - $value ),
				0.534 - 0.534 * ( 1 - $value ),
				0.131 + 0.869 * ( 1 - $value ),
			)
		);
	}

	/**
	 * Apply saturate filter.
	 *
	 * @param float $value Saturate value (0-1).
	 */
	public function saturate( $value = 1 ) {
		$this->multiply(
			array(
				0.213 + 0.787 * $value,
				0.715 - 0.715 * $value,
				0.072 - 0.072 * $value,
				0.213 - 0.213 * $value,
				0.715 + 0.285 * $value,
				0.072 - 0.072 * $value,
				0.213 - 0.213 * $value,
				0.715 - 0.715 * $value,
				0.072 + 0.928 * $value,
			)
		);
	}

	/**
	 * Apply hue rotate filter.
	 *
	 * @param float $angle Angle in degrees.
	 */
	public function hue_rotate( $angle = 0 ) {
		$angle = deg2rad( $angle );
		$sin   = sin( $angle );
		$cos   = cos( $angle );

		$this->multiply(
			array(
				0.213 + $cos * 0.787 - $sin * 0.213,
				0.715 - $cos * 0.715 - $sin * 0.715,
				0.072 - $cos * 0.072 + $sin * 0.928,
				0.213 - $cos * 0.213 + $sin * 0.143,
				0.715 + $cos * 0.285 + $sin * 0.14,
				0.072 - $cos * 0.072 - $sin * 0.283,
				0.213 - $cos * 0.213 - $sin * 0.787,
				0.715 - $cos * 0.715 + $sin * 0.715,
				0.072 + $cos * 0.928 + $sin * 0.072,
			)
		);
	}

	/**
	 * Apply brightness filter.
	 *
	 * @param float $value Brightness value (0-1).
	 */
	public function brightness( $value = 1 ) {
		$this->linear( $value );
	}

	/**
	 * Apply contrast filter.
	 *
	 * @param float $value Contrast value (0-1).
	 */
	public function contrast( $value = 1 ) {
		$this->linear( $value, -( 0.5 * $value ) + 0.5 );
	}
}

/**
 * CSS filter solver (SPSA algorithm over filter parameter space).
 */
class FilterSolver {

	/**
	 * Target color.
	 *
	 * @var FilterColor
	 */
	private $target;

	/**
	 * Reused color object for calculations.
	 *
	 * @var FilterColor
	 */
	private $reused_color;

	/**
	 * Constructor
	 *
	 * @param FilterColor $target Target color to match.
	 */
	public function __construct( $target ) {
		$this->target       = $target;
		$this->reused_color = new FilterColor( 0, 0, 0 );
	}

	/**
	 * Solve for filter values.
	 *
	 * @return array Array with 'values', 'loss', and 'filter' keys.
	 */
	public function solve() {
		$result = $this->solve_narrow( $this->solve_wide() );
		return array(
			'values' => $result['values'],
			'loss'   => $result['loss'],
			'filter' => $this->css( $result['values'] ),
		);
	}

	/**
	 * Generate CSS filter string from values.
	 *
	 * @param array $filters Filter values.
	 * @return string CSS filter string.
	 */
	private function css( $filters ) {
		return sprintf(
			'brightness(0) saturate(100%%) invert(%d%%) sepia(%d%%) saturate(%d%%) hue-rotate(%ddeg) brightness(%d%%) contrast(%d%%)',
			round( $filters[0] ),
			round( $filters[1] ),
			round( $filters[2] ),
			round( $filters[3] * 3.6 ),
			round( $filters[4] ),
			round( $filters[5] )
		);
	}

	/**
	 * Calculate loss for given filter values.
	 *
	 * @param array $filters Filter values.
	 * @return float Loss value
	 */
	private function loss( $filters ) {
		$color = $this->reused_color;
		$color->set( 0, 0, 0 );

		$color->invert( $filters[0] / 100 );
		$color->sepia( $filters[1] / 100 );
		$color->saturate( $filters[2] / 100 );
		$color->hue_rotate( $filters[3] * 3.6 );
		$color->brightness( $filters[4] / 100 );
		$color->contrast( $filters[5] / 100 );

		return abs( $color->r - $this->target->r ) +
			abs( $color->g - $this->target->g ) +
			abs( $color->b - $this->target->b );
	}

	/**
	 * Fix value within bounds for given index
	 *
	 * @param float $value Value to fix.
	 * @param int   $idx   Index of the value.
	 * @return float Fixed value
	 */
	private function fix( $value, $idx ) {
		$max = 100;
		if ( 2 === $idx ) {
			$max = 7500;
		} elseif ( 4 === $idx || 5 === $idx ) {
			$max = 200;
		}

		if ( 3 === $idx ) {
			if ( $value > $max ) {
				$value = fmod( $value, $max );
			} elseif ( $value < 0 ) {
				$value = $max + fmod( $value, $max );
			}
		} elseif ( $value < 0 ) {
				$value = 0;
		} elseif ( $value > $max ) {
			$value = $max;
		}

		return $value;
	}

	/**
	 * Simultaneous Perturbation Stochastic Approximation algorithm.
	 *
	 * @param int   $a      Algorithm parameter.
	 * @param array $a_arr  Algorithm parameter array.
	 * @param int   $c      Algorithm parameter.
	 * @param array $values Initial values.
	 * @param int   $iters  Number of iterations.
	 * @return array Best result.
	 */
	private function spsa( $a, $a_arr, $c, $values, $iters ) {
		$alpha     = 1;
		$gamma     = 0.16666666666666666;
		$best      = null;
		$best_loss = INF;
		$n         = count( $values );

		for ( $k = 0; $k < $iters; $k++ ) {
			$ck        = $c / pow( $k + 1, $gamma );
			$deltas    = array();
			$high_args = array();
			$low_args  = array();

			for ( $i = 0; $i < $n; $i++ ) {
				$deltas[ $i ]    = wp_rand( 0, 1 ) ? 1 : -1;
				$high_args[ $i ] = $values[ $i ] + $ck * $deltas[ $i ];
				$low_args[ $i ]  = $values[ $i ] - $ck * $deltas[ $i ];
			}

			$loss_diff = $this->loss( $high_args ) - $this->loss( $low_args );

			for ( $i = 0; $i < $n; $i++ ) {
				$g            = $loss_diff / ( 2 * $ck ) * $deltas[ $i ];
				$ak           = $a_arr[ $i ] / pow( $a + $k + 1, $alpha );
				$values[ $i ] = $this->fix( $values[ $i ] - $ak * $g, $i );
			}

			$loss = $this->loss( $values );
			if ( $loss < $best_loss ) {
				$best      = $values;
				$best_loss = $loss;
			}
		}

		return array(
			'values' => $best,
			'loss'   => $best_loss,
		);
	}

	/**
	 * Solve with wide search parameters
	 *
	 * @return array Best result from wide search
	 */
	private function solve_wide() {
		$a     = 5;
		$c     = 15;
		$a_arr = array( 60, 180, 18000, 600, 1.2, 1.2 );

		$best = array( 'loss' => INF );
		for ( $i = 0; $best['loss'] > 25 && $i < 3; $i++ ) {
			$initial = array( 50, 20, 3750, 50, 100, 100 );
			$result  = $this->spsa( $a, $a_arr, $c, $initial, 1000 );
			if ( $result['loss'] < $best['loss'] ) {
				$best = $result;
			}
		}
		return $best;
	}

	/**
	 * Solve with narrow search parameters.
	 *
	 * @param array $wide Result from wide search.
	 * @return array Best result from narrow search.
	 */
	private function solve_narrow( $wide ) {
		$a     = $wide['loss'];
		$c     = 2;
		$a1    = $a + 1;
		$a_arr = array( 0.25 * $a1, 0.25 * $a1, $a1, 0.25 * $a1, 0.2 * $a1, 0.2 * $a1 );
		return $this->spsa( $a, $a_arr, $c, $wide['values'], 500 );
	}
}
