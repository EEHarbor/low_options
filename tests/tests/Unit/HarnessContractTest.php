<?php

namespace LowOptionsTests;

class HarnessContractTest extends TestCase
{
    /**
     * Match real EE DB_result empty-row, index and named-column behavior.
     *
     * @return void
     */
    public function testDatabaseResultContract(): void
    {
        $query = new Query();
        self::assertSame(array(), $query->row());
        self::assertSame(array(), $query->row('missing'));
        $query = new Query();
        $query->rows = array(array('id' => 1, 'nullable' => null), array('id' => 2, 'nullable' => 'value'));
        self::assertSame(2, $query->row(1)->id);
        self::assertSame(2, $query->row(99)->id);
        self::assertSame(array('id' => 2, 'nullable' => 'value'), $query->row_array(99));
        self::assertNull($query->row('nullable'));
        self::assertSame(1, $query->row('missing')->id);
        self::assertSame(1, $query->row('id'));
        self::assertSame(array('id' => 2, 'nullable' => 'value'), $query->row(1, 'array'));
    }

    /**
     * Keep missing configuration false and retain explicit zero values.
     *
     * @return void
     */
    public function testConfigurationContract(): void
    {
        $config = new Config();
        self::assertFalse($config->item('missing'));
        $config->items['zero'] = 0;
        $config->items['nullable'] = null;
        self::assertSame(0, $config->item('zero'));
        self::assertFalse($config->item('nullable'));
    }

    /**
     * Preserve GET/POST separation, POST priority and nullable absence.
     *
     * @return void
     */
    public function testInputContract(): void
    {
        $input = $this->ee->input;
        $input->get = array('shared' => 'get', 'get-only' => 'get', 'nullable' => 'fallback');
        $input->post = array('shared' => 0, 'post-only' => false, 'nullable' => null);
        self::assertSame('get', $input->get('shared'));
        self::assertSame(0, $input->post('shared'));
        self::assertSame(0, $input->get_post('shared'));
        self::assertSame('get', $input->get_post('get-only'));
        self::assertFalse($input->get('post-only'));
        self::assertFalse($input->post('get-only'));
        self::assertFalse($input->get_post('post-only'));
        self::assertSame('fallback', $input->get_post('nullable'));
        $security = new ServiceRecorder();
        $security->result = 'cleaned';
        $this->ee->services['Security/XSS'] = $security;
        self::assertSame('cleaned', $input->get_post('shared', true));
        self::assertSame(array(array('clean', array(0))), $security->calls);
    }

    /**
     * Match EE's parameter defaults, aliases and url_title exception.
     *
     * @return void
     */
    public function testTemplateParameterContract(): void
    {
        $template = new Template();
        self::assertFalse($template->fetch_param('missing'));
        self::assertSame('fallback', $template->fetch_param('missing', 'fallback'));
        foreach (array('y' => 'yes', 'on' => 'yes', 'n' => 'no', 'off' => 'no') as $input => $expected) {
            $template->params['value'] = $input;
            $template->params['url_title'] = $input;
            self::assertSame($expected, $template->fetch_param('value'));
            self::assertSame($input, $template->fetch_param('url_title'));
        }
        $template->params['value'] = null;
        self::assertSame('fallback', $template->fetch_param('value', 'fallback'));
        $template->params['value'] = false;
        self::assertFalse($template->fetch_param('value'));
    }
}
