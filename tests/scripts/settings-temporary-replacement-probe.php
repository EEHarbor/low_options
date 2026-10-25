<?php
$archive=$argv[1];$prefix=$argv[2];$GLOBALS['setter_throw']=$argv[3]==='throw';$GLOBALS['setter_events']=array();$GLOBALS['setter_destroyed']=false;$GLOBALS['setter_error']=new RuntimeException('owned database update failure');
final class OwnedTemporaryReplacement {
    /**
     * Supply one native writable byte.
     *
     * @return string
     */
    public function __toString() {$GLOBALS['setter_events'][]='convert';return 'l';}
    /**
     * Observe original replacement lifetime through the database write.
     *
     * @return void
     */
    public function __destruct() {$GLOBALS['setter_destroyed']=true;$GLOBALS['setter_events'][]='destroy';}
}
final class OwnedSetterQuery {
    /**
     * Supply an existing fieldtype row.
     *
     * @return int
     */
    public function num_rows(){return 1;}
    /**
     * Supply the canonical stored string.
     *
     * @param string $field
     * @return string
     */
    public function row($field){return base64_encode(serialize('abc'));}
}
final class OwnedSetterDatabase {
    /**
     * Retain the field selection boundary.
     *
     * @param string $field
     * @return self
     */
    public function select($field){return $this;}
    /**
     * Retain the fieldtype-name filter.
     *
     * @param string $field
     * @param mixed $value
     * @return self
     */
    public function where($field,$value){return $this;}
    /**
     * Return a fresh settings query.
     *
     * @param string $table
     * @return OwnedSetterQuery
     */
    public function get($table){return new OwnedSetterQuery();}
    /**
     * Observe the original replacement before a successful or throwing update.
     *
     * @param string $table
     * @param array $data
     * @return bool
     * @throws RuntimeException
     */
    public function update($table,$data){$GLOBALS['setter_events'][]=array('db-update','destroyed'=>$GLOBALS['setter_destroyed'],'value'=>unserialize(base64_decode($data['settings'])));if($GLOBALS['setter_throw']){throw $GLOBALS['setter_error'];}return true;}
}
/**
 * Resolve the owned writer.
 *
 * @return object
 */
function ee(){return $GLOBALS['setter_app'];}
$GLOBALS['setter_app']=(object)array('db'=>new OwnedSetterDatabase());
spl_autoload_register(static function($class)use($archive,$prefix){if(strpos($class,$prefix.'\\')===0){$path='phar://'.$archive.'/'.str_replace('\\','/',substr($class,strlen($prefix)+1)).'.php';if(file_exists($path)){require $path;}}});
$class=$prefix.'\\FluxCapacitor\\Conduit\\GlobalFieldSettings';$settings=(new ReflectionClass($class))->newInstanceWithoutConstructor();$settings->fieldtype_name='owned';
try{$value=$settings->updateSetting(1,new OwnedTemporaryReplacement());$outcome=array('value'=>$value);}catch(Throwable $error){$outcome=array('exception_same'=>$error===$GLOBALS['setter_error'],'exception'=>get_class($error));}
echo json_encode(array('outcome'=>$outcome,'events'=>$GLOBALS['setter_events'],'destroyed'=>$GLOBALS['setter_destroyed']),JSON_THROW_ON_ERROR),"\n";
