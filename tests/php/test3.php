<?php

namespace Illuminate\System\App\Libraries;

use Illuminate\System\App\Collections\BaseCollection;
use Illuminate\System\App\Entities\BaseEntity;
use Ramsey\Uuid\Uuid;
/**
* Lorem ipsum.
*
* @param string $param1
* @param bool   $param2 lorem ipsum
* @param  string $param3 lorem ipsum
*
* @return int lorem ipsum
*/
trait RevisionableTrait
{
private $revisions_tbl_suffix = '_revisions';
/**
* Lorem ipsum.
*
* @param string $param1
* @param bool   $param2 lorem ipsum
* @param  string $param3 lorem ipsum
*
* @return int lorem ipsum
*/
public function saveRevision(BaseCollection $old_data, $new_data = null, $options = [])
{
$table = $this->getRevisionsTableName();

$entity = $this->collectionToRevisionEntity($old_data);

$data = $entity->getData();

$data = $this->filterFields($data);

$uuid1 = Uuid::uuid1();

$data['rev_id'] = $uuid1->toString();
$data['rev_hash'] = $entity->hash();
$data['rev_date'] = Date::now()->toSql();
$data['rev_author'] = $this->user->getFullName();

$status = $this->db->insert($table, $data);
return $status;
}

private function collectionToRevisionEntity(BaseCollection $collection)
{
$entity = $collection->getData();
if (empty($entity[0]) || !$entity[0] instanceof BaseEntity) {
throw new \InvalidArgumentException('Data for revision is invalid!');
}
return $entity[0];
}


public function getRevisions($item, $options = [])
{
$id = $item->getId();
if (empty($id)) {
return [];
}



$opts = [];
$opts['table'] = $this->getRevisionsTableName();
$opts['id'] = $item->getId();
$opts['editor'] = true;
$opts['order_by'] = 'rev_date DESC';

$data = $this->get($opts, false, false);

return $data;
}

public function getRevision($id, $rev_id, $options = [])
{
$opts = [];
$opts['id'] = $id;
$current = $this->getRow($opts);

$opts = [];
$opts['table'] = $this->getRevisionsTableName();
$opts['rev_id'] = $rev_id;
$revision = $this->getRow($opts);

$fields = $this->getTableFields();

return [$fields, $current, $revision];
}

public function restoreRevision($id, $rev_id, $options = [])
{
$opts = [];
$opts['id'] = $id;
$current = $this->getRow($opts);

$status = $this->saveRevision($current);
if (!$status) {
throw new \Exception('Can not save content as revision');
}

$opts = [];
$opts['table'] = $this->getRevisionsTableName();
$opts['rev_id'] = $rev_id;
$revision = $this->getRow($opts);

$entity = $this->collectionToRevisionEntity($revision);

$data = $entity->getData(true);

$opts = [];
$opts['no_revision'] = true;
$opts['no_log'] = true;
$opts['id'] = $id;
$status = $this->update($data, $opts, false, false);
if (!$status) {
throw new \Exception('Can not restore revision '.$rev_id);
}


return $current->getEditUrl();
}


public function isRevisionable($options = [])
{
if (isset($options['no_revision'])) {
return false;
}
return true;
}

private function getRevisionsTableName()
{
return $this->tbl.$this->revisions_tbl_suffix;
}

}
