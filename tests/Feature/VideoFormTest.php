<?php

namespace Tests\Feature;

use Tests\TestCase;

class VideoFormTest extends TestCase
{
    public function test_form_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Create a doctor video');
    }

    public function test_submission_requires_all_fields(): void
    {
        $this->post('/generate-video')->assertSessionHasErrors([
            'employee_code', 'prefix', 'doctor_name', 'city', 'photo',
        ]);
    }
}
