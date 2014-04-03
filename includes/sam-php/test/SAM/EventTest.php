<?php

class SAM_EventTest extends SAMTestCase
{
	public function testAll()
	{
		authorizeFromEnv();
		$d = SAM_Event::all();
		$this->assertTrue(count($d) > 0);
	}

  public function testAllFilters()
  {
    authorizeFromEnv();
    $d = SAM_Event::all(array("count" => 10, "type" => "story:asset:*"));
    $this->assertTrue(count($d) > 0);
  }

  public function testRetrieve()
  {
    authorizeFromEnv();
    $d = SAM_Event::retrieve('52fa82f7afb78eab37000006');
    $this->assertEqual($d->id, "52fa82f7afb78eab37000006");
    $this->assertEqual($d->type, "story:asset:tag");
  }
}
