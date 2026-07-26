<?php

declare(strict_types=1);

test('that true is true', function () {
    $this->assertNotEmpty(sys_get_temp_dir());
});
