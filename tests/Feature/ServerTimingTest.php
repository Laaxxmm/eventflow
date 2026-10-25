<?php

it('reports the app time in a Server-Timing header', function () {
    $header = $this->get('/up')->assertOk()->headers->get('Server-Timing');

    expect($header)->toMatch('/^app;desc="EventFlow";dur=\d+(\.\d)?$/');
});
