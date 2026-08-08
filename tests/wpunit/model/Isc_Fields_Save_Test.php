<?php

namespace ISC\Tests\WPUnit\Model;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Model;

/**
 * Test ISC_Model::isc_fields_save().
 */
class Isc_Fields_Save_Test extends WPTestCase {

	/**
	 * Test ISC_Model::isc_fields_save() stores valid AI labels as lowercase strings.
	 */
	public function test_isc_fields_save_stores_valid_ai_label(): void {
		$attachment_id = self::factory()->attachment->create();
		$model         = new ISC_Model();

		$model->isc_fields_save(
			[ 'ID' => $attachment_id ],
			[ 'isc_image_ai' => 'AI-GENERATED' ]
		);

		$this->assertSame( 'ai-generated', get_post_meta( $attachment_id, 'isc_image_ai', true ) );
	}

	/**
	 * Test ISC_Model::isc_fields_save() rejects invalid AI labels.
	 */
	public function test_isc_fields_save_rejects_invalid_ai_label(): void {
		$attachment_id = self::factory()->attachment->create();
		$model         = new ISC_Model();

		$model->isc_fields_save(
			[ 'ID' => $attachment_id ],
			[ 'isc_image_ai' => 'invalid-value' ]
		);

		$this->assertSame( '', get_post_meta( $attachment_id, 'isc_image_ai', true ) );
	}
}
