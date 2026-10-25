<?php
final class DirectOffsetContainer extends ArrayObject {
 
 /**
  * Retain neither raw ArrayAccess argument after assignment.
  *
  * @param mixed $key
  * @param mixed $value
  * @return void
  * @throws RuntimeException
  */
 public function offsetSet($key, $value): void {$GLOBALS['aa_lifetime_events'][]='assign';if($GLOBALS['aa_lifetime_case']==='assign-throws'){throw $GLOBALS['aa_lifetime_error'];}}
}
final class TemporaryOffsetArgument {
 private $name;
 
 /**
  * Name the original temporary argument.
  *
  * @param string $name
  * @return void
  */
 public function __construct($name){$this->name=$name;}
 
 /**
  * Observe argument release after the original write boundary.
  *
  * @return void
  */
 public function __destruct(){$GLOBALS['aa_lifetime_events'][]=$this->name.'-destroy';}
}
final class OffsetLifetimeDatabase {
 
 /**
  * Retain the original field selection.
  *
  * @param string $field
  * @return self
  */
 public function select($field){return $this;}
 /**
  * Retain the original fieldtype filter.
  *
  * @param string $field
  * @param mixed $value
  * @return self
  */
 public function where($field,$value){return $this;}
 /**
  * Supply the original query boundary.
  *
  * @param string $table
  * @return self
  */
 public function get($table){return $this;}
 /**
  * Supply one stored fieldtype.
  *
  * @return int
  */
 public function num_rows(){return 1;}
 
 /**
  * Supply a native ArrayAccess root.
  *
  * @param string $field
  * @return string
  */
 public function row($field){return base64_encode(serialize(new DirectOffsetContainer()));}
 
 /**
  * Observe successful or throwing database completion.
  *
  * @param string $table
  * @param array $data
  * @return bool
  * @throws RuntimeException
  */
 public function update($table,$data){$GLOBALS['aa_lifetime_events'][]='db-update';if($GLOBALS['aa_lifetime_case']==='db-throws'){throw $GLOBALS['aa_lifetime_error'];}return true;}
}
/**
 * Resolve the owned Active Record collaborator.
 *
 * @return object
 */
function ee(){return $GLOBALS['aa_lifetime_app'];}
$archive=$argv[1];$prefix=$argv[2];$GLOBALS['aa_lifetime_case']=$argv[3];$GLOBALS['aa_lifetime_error']=new RuntimeException('owned AA callback failure');$GLOBALS['aa_lifetime_events']=array();$GLOBALS['aa_lifetime_app']=(object)array('db'=>new OffsetLifetimeDatabase());
spl_autoload_register(static function($class)use($archive,$prefix){if(strpos($class,$prefix.'\\')===0){$path='phar://'.$archive.'/'.str_replace('\\','/',substr($class,strlen($prefix)+1)).'.php';if(file_exists($path)){require $path;}}});
$class=$prefix.'\\FluxCapacitor\\Conduit\\GlobalFieldSettings';$object=(new ReflectionClass($class))->newInstanceWithoutConstructor();$object->fieldtype_name='owned';try{$object->updateSetting(new TemporaryOffsetArgument('key'),new TemporaryOffsetArgument('value'));}catch(RuntimeException $error){if($error!==$GLOBALS['aa_lifetime_error']){throw $error;}$GLOBALS['aa_lifetime_events'][]='caught-same';}echo json_encode($GLOBALS['aa_lifetime_events'],JSON_THROW_ON_ERROR),"\n";
