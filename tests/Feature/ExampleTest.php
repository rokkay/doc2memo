<?php

test('the application redirects to tenders index', function () {
    $response = $this->get('/');

    $response->assertRedirect('/tenders');
});
