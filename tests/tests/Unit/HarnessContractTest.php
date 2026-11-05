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
     * Return independent database results and distinguish table-specific lookups.
     *
     * @return void
     */
    public function testDatabaseTableResultSnapshotContract(): void
    {
        $database = new Database();
        $site = new Query();
        $site->record = array('site_label' => 'Other');
        $database->results['sites'] = $site;
        $database->record = array('id' => 17);
        $result = $database->get();
        self::assertInstanceOf(Query::class, $result);
        self::assertNotSame($database, $result);
        self::assertSame(17, $result->row('id'));
        $database->record = array('id' => 18);
        self::assertSame(17, $result->row('id'));
        self::assertSame($site, $database->get('sites', 1));
        self::assertSame($site, $database->get_where('sites', array('site_id' => 2), 1));
        self::assertNotSame($database, $database->get('other'));
        self::assertSame(18, $database->get('other')->row('id'));
    }

    /**
     * Resolve fresh builders with local query/cache state and shared responses.
     *
     * @return void
     */
    public function testDatabaseServiceCreatesFreshBuilders(): void
    {
        $database = $this->ee->db;
        $database->where('pending', 17);
        $database->data_cache['table_names'] = array('kept');
        $first = ee('db');
        $second = ee('db');
        self::assertNotSame($database, $first);
        self::assertNotSame($database, $second);
        self::assertNotSame($first, $second);
        self::assertSame(array(), $first->calls);
        self::assertSame(array(), $second->calls);
        self::assertSame(array(), $first->data_cache);
        $database->record = array('id' => 18);
        $database->tableExists = true;
        $database->count = 2;
        $site = new Query();
        $site->record = array('site_label' => 'Other');
        $database->results['sites'] = $site;
        $first->where('site_id', 2);
        self::assertSame(18, $first->get()->row('id'));
        self::assertSame($site, $second->get('sites'));
        self::assertTrue($second->table_exists('sites'));
        self::assertSame(2, $second->count_all_results('sites'));
        self::assertSame($database->queryResult, $first->query('SELECT 1'));
        self::assertSame($database->sharedTrace, $first->sharedTrace);
        self::assertSame($database->sharedTrace, $second->sharedTrace);
        self::assertContains(array('where', array('site_id', 2)), $database->sharedTrace->calls);
        self::assertContains(array('count_all_results', array('sites')), $database->sharedTrace->calls);
        self::assertSame(array(array('where', array('pending', 17))), $database->calls);
        self::assertSame(array('table_names' => array('kept')), $database->data_cache);
        self::assertNotContains(array('where', array('site_id', 2)), $second->calls);
    }

    /**
     * Match the current EE Facade's replacement guard without losing old state.
     *
     * @return void
     */
    public function testNativeFacadeRejectsExistingServiceReplacement(): void
    {
        $this->ee->set('custom', 'original');
        try {
            $this->ee->set('custom', 'replacement');
            self::fail('The fixture allowed replacement of an already-loaded service.');
        } catch (\RuntimeException $error) {
            self::assertSame('Cannot overwrite custom on the loader.', $error->getMessage());
            self::assertSame('original', $this->ee->custom);
        }
        self::assertTrue($this->ee->has('config'));
        $this->expectException(\RuntimeException::class);
        $this->ee->set('config', new Config());
    }

    /**
     * Track null registrations and unset removed services rather than nulling them.
     *
     * @return void
     */
    public function testNativeFacadeNullRegistrationAndRemoval(): void
    {
        $this->ee->set('custom', null);
        self::assertTrue($this->ee->has('custom'));
        self::assertNull($this->ee->custom);
        $this->ee->remove('custom');
        self::assertFalse($this->ee->has('custom'));
        self::assertArrayNotHasKey('custom', get_object_vars($this->ee));
        $this->ee->set('custom', 'fresh');
        self::assertSame('fresh', $this->ee->custom);
    }

    /**
     * Reload a declared collaborator without retaining a stale public value.
     *
     * @dataProvider nativeCollaboratorProvider
     * @param string $name
     * @param string $class
     * @return void
     */
    public function testNativeFacadeReloadsDeclaredCollaborator($name, $class): void
    {
        $original = $this->ee->$name;
        self::assertTrue($this->ee->has($name));
        $this->ee->remove($name);
        self::assertFalse($this->ee->has($name));
        self::assertArrayNotHasKey($name, get_object_vars($this->ee));
        $replacement = new $class();
        $this->ee->set($name, $replacement);
        self::assertTrue($this->ee->has($name));
        self::assertSame($replacement, $this->ee->$name);
        self::assertSame($replacement, ee()->$name);
        self::assertNotSame($original, $this->ee->$name);
        $this->ee->remove($name);
        self::assertFalse($this->ee->has($name));
    }

    /**
     * Provide declared native collaborators with observable object identity.
     *
     * @return array
     */
    public static function nativeCollaboratorProvider(): array
    {
        return array(array('db', Database::class), array('logger', Logger::class));
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
