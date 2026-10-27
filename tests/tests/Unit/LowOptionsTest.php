<?php

namespace LowOptionsTests;

require_once LOW_OPTIONS_ROOT . '/low_options/pi.low_options.php';

class LowOptionsTest extends TestCase
{
    private $plugin;
    private static $field = 0;

    /**
     * Create an isolated EE request and strict diagnostic handler.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = (new \ReflectionClass('Low_options'))->newInstanceWithoutConstructor();
    }

    /**
     * Configure one field lookup using EE's hydrated settings contract.
     *
     * @param array $settings
     * @param string $items
     * @return string
     */
    private function field(array $settings = array(), $items = '')
    {
        $name = 'test_field_' . ++self::$field;
        $this->ee->model->field->first = (object) array(
            'field_id' => 17,
            'field_list_items' => $items,
            'field_settings' => $settings,
        );
        return $name;
    }

    /**
     * Verify constructor loads real archive bridge and addon settings.
     *
     * @return void
     */
    public function testConstructorLoadsRealArchiveBridgeAndAddonSettings(): void
    {
        $plugin = new \Low_options();
        self::assertSame('2.2.0', $plugin->version);
        self::assertSame('low_options', $plugin->flux->shortname);
        self::assertSame(array('table_names' => array()), $this->ee->db->data_cache);
        self::assertSame(array(), $this->ee->db->calls);
    }

    /**
     * Verify missing field returns no results and caches miss.
     *
     * @return void
     */
    public function testMissingFieldReturnsNoResultsAndCachesMiss(): void
    {
        $field = 'missing_' . ++self::$field;
        self::assertSame('NO RESULTS', $this->plugin->$field());
        self::assertSame('NO RESULTS', $this->plugin->$field());
        self::assertSame(array('ChannelField'), $this->ee->model->calls);
        self::assertContains(array('filter', array('site_id', 'IN', array(1, 2, 0))), $this->ee->model->field->calls);
        self::assertSame(array(1, 2), $this->ee->TMPL->site_ids);
    }

    /**
     * Verify get uses field parameter and list items take precedence.
     *
     * @return void
     */
    public function testGetUsesFieldParameterAndListItemsTakePrecedence(): void
    {
        $field = $this->field(array('options' => array('ignored' => 'Ignored')), " one\ntwo\none ");
        $this->ee->TMPL->params['field'] = $field;
        $result = json_decode($this->plugin->get(), true);
        self::assertSame(array(
            array('option:group' => '', 'options' => array(array('option:value' => 'one', 'option:label' => 'one'))),
            array('option:group' => '', 'options' => array(array('option:value' => 'two', 'option:label' => 'two'))),
        ), $result);
        self::assertSame($this->ee->TMPL->tagdata, $this->ee->TMPL->parsed[0][0]);
    }

    /**
     * Verify get without field has no results.
     *
     * @return void
     */
    public function testGetWithoutFieldHasNoResults(): void
    {
        self::assertSame('NO RESULTS', $this->plugin->get());
        self::assertSame(array(), $this->ee->model->calls);
    }

    /**
     * Keep grouped/flat options and numeric zero meaningful.
     *
     * @dataProvider settingsProvider
     * @param array $settings
     * @param array $expected
     * @return void
     */
    public function testSettingsOptions(array $settings, array $expected): void
    {
        $field = $this->field($settings);
        self::assertSame($expected, json_decode($this->plugin->$field(), true));
    }

    /**
     * Provide settings vectors.
     *
     * @return array
     */
    public static function settingsProvider(): array
    {
        return array(
            'options override value label pairs' => array(
                array('options' => array(0 => 'Zero', 'x' => 'Ex'), 'value_label_pairs' => array('ignored' => 'Ignored')),
                array(
                    array('option:group' => '', 'options' => array(array('option:value' => 0, 'option:label' => 'Zero'))),
                    array('option:group' => '', 'options' => array(array('option:value' => 'x', 'option:label' => 'Ex'))),
                ),
            ),
            'invalid options falls back to pairs' => array(
                array('options' => 'malformed', 'value_label_pairs' => array('Group' => array('a' => 'A'))),
                array(array('option:group' => 'Group', 'options' => array(array('option:value' => 'a', 'option:label' => 'A')))),
            ),
        );
    }

    /**
     * Apply include/exclude semantics consistently within option groups.
     *
     * @dataProvider ignoreProvider
     * @param string $ignore
     * @param array $expected
     * @return void
     */
    public function testIgnoreFiltersGroupsAndFlatOptions($ignore, array $expected): void
    {
        $field = $this->field(array('options' => array('Group' => array('a' => 'A', 'b' => 'B'), 'c' => 'C')));
        $this->ee->TMPL->params['ignore'] = $ignore;
        self::assertSame($expected, json_decode($this->plugin->$field(), true));
    }

    /**
     * Provide ignore vectors.
     *
     * @return array
     */
    public static function ignoreProvider(): array
    {
        return array(
            array('a|c', array(array('option:group' => 'Group', 'options' => array(array('option:value' => 'b', 'option:label' => 'B'))))),
            array('not a|c', array(
                array('option:group' => 'Group', 'options' => array(array('option:value' => 'a', 'option:label' => 'A'))),
                array('option:group' => '', 'options' => array(array('option:value' => 'c', 'option:label' => 'C'))),
            )),
            array('not absent', array(array('option:group' => 'Group', 'options' => array()))),
        );
    }

    /**
     * Verify existing values are unique naturally sorted and reversed.
     *
     * @return void
     */
    public function testExistingValuesAreUniqueNaturallySortedAndReversed(): void
    {
        $field = $this->field();
        $this->ee->model->entries->values = array('item10|item2', "item1\nitem2", '0');
        $this->ee->TMPL->params['sort'] = 'desc';
        $result = json_decode($this->plugin->$field(), true);
        self::assertSame(array('item10', 'item2', 'item1', 0), array_map(function ($row) {
            return $row['options'][0]['option:value'];
        }, $result));
        self::assertContains(array('filter', array('field_id_17', 'IS NOT', null)), $this->ee->model->entries->calls);
        self::assertContains(array('filter', array('field_id_17', '!=', '')), $this->ee->model->entries->calls);
    }

    /**
     * Verify empty existing result returns no results.
     *
     * @return void
     */
    public function testEmptyExistingResultReturnsNoResults(): void
    {
        $field = $this->field(array('options' => null, 'value_label_pairs' => false));
        self::assertSame('NO RESULTS', $this->plugin->$field());
    }

    /**
     * Verify show empty filters flat and grouped and retains cached options for next request.
     *
     * @return void
     */
    public function testShowEmptyFiltersFlatAndGroupedAndRetainsCachedOptionsForNextRequest(): void
    {
        $field = $this->field(array('options' => array('Group' => array('a' => 'A', 'b' => 'B'), 'c' => 'C', 'd' => 'D')));
        $this->ee->model->entries->values = array('a|c');
        $this->ee->TMPL->params['show_empty'] = 'no';
        $result = json_decode($this->plugin->$field(), true);
        self::assertCount(2, $result);
        self::assertSame('a', $result[0]['options'][0]['option:value']);
        self::assertSame('c', $result[1]['options'][0]['option:value']);
        $this->ee->TMPL->params['show_empty'] = 'yes';
        self::assertCount(3, json_decode($this->plugin->$field(), true));
    }

    /**
     * Verify existing query honors template filters.
     *
     * @return void
     */
    public function testExistingQueryHonorsTemplateFilters(): void
    {
        $this->ee->TMPL->params = array('channel' => 'news|blog', 'status' => 'not closed', 'category' => 'not 3|4');
        $this->ee->model->entries->values = array('A|B', "C\nD", 'A');
        self::assertSame(array('A', 'B', 'C', 'D'), $this->invoke($this->plugin, '_get_existing', array(17)));
        $calls = $this->ee->model->entries->calls;
        self::assertContains(array('filter', array('Channel.channel_name', 'IN', array('news', 'blog'))), $calls);
        self::assertContains(array('filter', array('status', 'NOT IN', array('closed'))), $calls);
        self::assertContains(array('filter', array('Categories.cat_id', 'NOT IN', array('3', '4'))), $calls);
        self::assertContains(array('filter', array('entry_date', '<', 1700000000)), $calls);
        self::assertContains(array('orFilter', array('expiration_date', '>', 1700000000)), $calls);
    }

    /**
     * Verify future expired and empty status remove optional restrictions.
     *
     * @return void
     */
    public function testFutureExpiredAndEmptyStatusRemoveOptionalRestrictions(): void
    {
        $this->ee->TMPL->params = array('show_future_entries' => 'yes', 'show_expired' => 'yes', 'status' => '');
        self::assertSame(array(), $this->invoke($this->plugin, '_get_existing', array(0)));
        self::assertSame(array(
            array('filter', array('site_id', 'IN', array(1, 2))),
            array('fields', array('entry_id', 'channel_id', 'field_id_0')),
            array('filter', array('field_id_0', '!=', '')),
            array('filter', array('field_id_0', 'IS NOT', null)),
            array('pluck', array('field_id_0')),
        ), $this->ee->model->entries->calls);
    }

    /**
     * Filter category conjunction tokens and constrain empty results.
     *
     * @dataProvider conjunctionProvider
     * @param array $rows
     * @param array $expected
     * @return void
     */
    public function testCategoryConjunction(array $rows, array $expected): void
    {
        $this->ee->TMPL->params['category'] = '1&bad&2';
        $this->ee->db->queryResult->rows = $rows;
        $this->ee->db->queryResult->num_rows = count($rows);
        $this->invoke($this->plugin, '_get_existing', array(17));
        self::assertContains(array('filter', array('entry_id', 'IN', $expected)), $this->ee->model->entries->calls);
        self::assertStringContainsString('WHERE cat_id IN (1,2)', $this->ee->db->calls[0][1][0]);
        self::assertStringContainsString('HAVING num = 2', $this->ee->db->calls[0][1][0]);
    }

    /**
     * Provide conjunction vectors.
     *
     * @return array
     */
    public static function conjunctionProvider(): array
    {
        return array(array(array(), array(0)), array(array(array('entry_id' => 5), array('entry_id' => 8)), array(5, 8)));
    }

    /**
     * Exercise string grammar, zero, false and empty inputs.
     *
     * @dataProvider parameterProvider
     * @param mixed $input
     * @param array $expected
     * @return void
     */
    public function testParameterGrammar($input, array $expected): void
    {
        self::assertSame($expected, $this->invoke($this->plugin, '_explode_param', array($input)));
    }

    /**
     * Provide parameter vectors.
     *
     * @return array
     */
    public static function parameterProvider(): array
    {
        return array(
            array('NOT red|blue&green', array(array('red', 'blue', 'green'), false)),
            array('', array(array(''), true)),
            array(false, array(array(''), true)),
            array('0|01|1.5|-2', array(array('0', '01', '1.5', '-2'), true)),
            array('not ', array(array(''), false)),
            array(' not x ', array(array(' not x '), true)),
        );
    }

    /**
     * Verify zero option survives show empty when entry contains zero.
     *
     * @return void
     */
    public function testZeroOptionSurvivesShowEmptyWhenEntryContainsZero(): void
    {
        $field = $this->field(array('options' => array(0 => '0')));
        $this->ee->TMPL->params['show_empty'] = 'no';
        $this->ee->model->entries->values = array('0');
        self::assertNotSame('NO RESULTS', $this->plugin->$field());
    }

}
