<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_baseline_page_identifies_the_formal_project(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('MyBsPHP2')
            ->assertSee('W1 工程基线')
            ->assertSee('W1-5 Ready')
            ->assertSee('W1-6 工程检查')
            ->assertSee('查看当前后台原型')
            ->assertDontSee('Laravel Documentation');
    }
}
