<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;


abstract class AbstractResourceTest extends AbstractAPIv2Test {
    /**
     * The number of rows create when testing index endpoints.
     */
    const INDEX_ROWS = 4;

    /**
     * @var string The resource route.
     */
    protected $baseUrl = '/resources';
    /**
     * @var string The singular name of the resource.
     */
    protected $singular = '';
    /**
     * @var array A record that can be posted to the endpoint.
     */
    protected $record = ['body' => 'Hello world!', 'format' => 'markdown'];
    /**
     * @var string[] An array of field names that are okay to send to patch endpoints.
     */
    protected $patchFields;
    /**
     * @var string The name of the primary key of the resource.
     */
    protected $pk = '';

    /**
     * AbstractResourceTest constructor.
     *
     * Subclasses can override properties and then call this constructor to set defaults.
     *
     * @param null $name Required by PHPUnit.
     * @param array $data Required by PHPUnit.
     * @param string $dataName Required by PHPUnit.
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        if (empty($this->singluar)) {
            $this->singular = rtrim(ltrim($this->baseUrl, '/'), 's');
        }
        if (empty($this->pk)) {
            $this->pk = $this->singular.'ID';
        }

        if ($this->patchFields === null) {
            $this->patchFields = array_keys($this->record);
        }
    }

    /**
     * Test GET /resource/<id>.
     */
    public function testGet() {
        $row = $this->testPost();

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual($row, $r->getBody());
        $this->assertCamelCase($r->getBody());

        return $r->getBody();
    }

    /**
     * Test POST /resource.
     *
     * @return array Returns the new record.
     */
    public function testPost() {
        $result = $this->api()->post(
            $this->baseUrl,
            $this->record
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body[$this->pk]));
        $this->assertTrue($body[$this->pk] > 0);

        $this->assertRowsEqual($this->record, $body, true);

        return $body;
    }

    /**
     * Test PATCH /resource/<id> with a full record overwrite.
     */
    public function testPatchFull() {
        $row = $this->testGetEdit();
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());

        $this->assertRowsEqual($newRow, $r->getBody(), true);

        return $r->getBody();
    }

    /**
     * Test updating a field with PUT.
     *
     * @param string $action
     * @param mixed $val
     * @param string|null $col
     * @throws \Exception if the new record already has its field set to the target value.
     * @dataProvider providePutFields
     */
    public function testPutField($action, $val, $col = null) {
        if ($col === null) {
            $col = $action;
        }
        $row = $this->testPost();

        $before = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
        if ($before[$col] === $val) {
            $printVal = var_export($val, true);
            throw new \Exception("Unable to test PUT for {$this->singular} field: {$col} is already {$printVal}");
        }
        $urlAction = urlencode($action);
        $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/{$urlAction}", [$col => $val]);
        $after = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");

        $this->assertEquals($val, $after[$col]);
    }

    /**
     * Test GET /resource/<id>/edit.
     */
    public function testGetEdit() {
        $row = $this->testPost();

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}/edit"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $this->assertRowsEqual(arrayTranslate($this->record, ['name', 'body', 'format']), $r->getBody());
        $this->assertCamelCase($r->getBody());

        return $r->getBody();
    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    protected function modifyRow(array $row) {
        $newRow = [];

        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            if (in_array($key, ['name', 'body'])) {
                $value .= ' '.$dt->format(\DateTime::RSS);
            } elseif ($key === 'format') {
                $value = $value === 'markdown' ? 'text' : 'markdown';
            } elseif (stripos($key, 'id') === strlen($key) - 2) {
                $value++;
            }

            $newRow[$key] = $value;
        }

        return $newRow;
    }

    /**
     * The GET /resource/<id>/edit endpoint should have the same fields as that patch fields.
     *
     * This test helps to ensure that fields are added to the test as the endpoint is updated.
     */
    public function testGetEditFields() {
        $row = $this->testGetEdit();

        unset($row[$this->pk]);
        $rowFields = array_keys($row);
        sort($rowFields);

        $patchFields = $this->patchFields;
        sort($patchFields);

        $this->assertEquals($patchFields, $rowFields);
    }

    /**
     * Test PATCH /resource/<id> with a a single field update.
     *
     * Patch endpoints should be able to update every field on its own.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        $row = $this->testGetEdit();
        $patchRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [$field => $patchRow[$field]]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit");
        $this->assertSame($patchRow[$field], $newRow[$field]);
    }

    /**
     * Test DELETE /resource/<id>.
     */
    public function testDelete() {
        $row = $this->testPost();

        $r = $this->api()->delete(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
            $this->fail("The {$this->singular} did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a {$this->singular}.");
    }

    /**
     * Test GET /resource.
     *
     * The base class test can only test a minimum of functionality. Subclasses can make additional assertions on the
     * return value of this method.
     *
     * @return array Returns the fetched data.
     */
    public function testIndex() {
        // Insert a few rows.
        $rows = [];
        for ($i = 0; $i < static::INDEX_ROWS; $i++) {
            $rows[] = $this->testPost();
        }

        $indexUrl = $this->indexUrl();
        $r = $this->api()->get($indexUrl);
        $this->assertEquals(200, $r->getStatusCode());

        $dbRows = $r->getBody();
        $this->assertGreaterThan(self::INDEX_ROWS, count($dbRows));
        // The index should be a proper indexed array.
        for ($i = 0; $i < count($dbRows); $i++) {
            $this->assertArrayHasKey($i, $dbRows);
        }

        // There's not much we can really test here so just return and let subclasses do some more assertions.
        return [$rows, $dbRows];
    }

    /**
     * The endpoint's index URL.
     *
     * @return string
     */
    public function indexUrl() {
        return $this->baseUrl;
    }

    /**
     * Provide the patch fields in a way that can be consumed as a data provider.
     *
     * @return array Returns a data provider array.
     */
    public function providePatchFields() {
        $r = [];
        foreach ($this->patchFields as $field) {
            $r[$field] = [$field];
        }
        return $r;
    }

    /**
     * Provide fields for PUT operations in a format that is compatible with a data provider.
     *
     * @return array
     */
    public function providePutFields() {
        return [];
    }
}
