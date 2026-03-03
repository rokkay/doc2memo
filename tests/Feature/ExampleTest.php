<?php

test('the application redirects to tenders index', function (): void {
    $response = $this->get('/');

    $response->assertRedirect('/tenders');
});
