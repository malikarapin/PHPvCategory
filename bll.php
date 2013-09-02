<?php
/**
 * vskCategory Script
 * 
 * This class is responsible for processing category data.
 * Use your own data access layer, this class just provide an overview
 * almost any type of category dropdown, array, or ul / li listings 
 * @author irfan (Muhammad Irfan, irfan.nettech@gmail.com)
 * @package PHPvCategory
 * @license New BSD 
 * @version 1.0
 */
include_once("db.php");

class categoriesmgt {
		
	// Add / Update Category Record
	function process($fields, $filters, $isupdate, $lastinsertid = true, $queryanalysis = false)
    {	
	    $db = new DB;	
		$categoryid = 0;
        if ($isupdate)
		{
			$db->Update("categories", $fields, $filters, $queryanalysis);
			$categoryid = $filters['categoryid'];
		}
		else
		{
   		    $categoryid = $db->Insert("categories", $fields, $lastinsertid, $queryanalysis); 
		}
		// update level stats
		$level = categoriesmgt::preparelevel($categoryid);
		categoriesmgt::update_value($categoryid, "level", $level);
		return $categoryid;
    }
	
	
	function update_value($id, $fieldname, $value)
	{
		$db = new DB;	
		$db->Update("categories", array($fieldname => $value), array('categoryid' => $id));
	}
	
	function get_field_value($categoryid, $fieldname)
    {
		$db = new DB;
		return $db->ReturnValue("categories", $fieldname, array('categoryid' => $categoryid));
    }
		
	function delete($categoryid)
    {	
	    $db = new DB;	
		$db->Delete("categories", array('categoryid' => $categoryid));
	    // adjust parent categories
		$lst = categoriesmgt::fetch_record("categoryid",  array('parentid' => $categoryid), true, false);
	    if(count($lst) > 0)
	    {
			foreach($lst as $itm)
			{
				// reset parent as (0) for categories if current is parent
				categoriesmgt::update_value($itm->categoryid, "parentid", "0");
				// update level
				$level = categoriesmgt::preparelevel($itm->categoryid);
		        categoriesmgt::update_value($itm->categoryid, "level", $level);
			}
		}
        return true;
    }

	
    function has_child($categoryid, $isprivate)
    {
        $isprivatestr = "";
		$bind = array();
		
        if ($isprivate != 2)
		{
            $isprivatestr = " AND isprivate=:isprivate";
			$bind['isprivate'] = $isprivate;
		}

        $query = "SELECT COUNT(*) as total from categories WHERE parentid=:categoryid" . $isprivatestr . "";
		$bind['categoryid'] = $categoryid;
		
		$db = new DB;
		$total = $db->smartQuery(array(
		'sql' => $query,
		'par' => $bind,
		'ret' => 'col'
		 ));
		if($total > 0)
		    return true;
		else
		    return false;

    }
		
	// pagenumber 0 -> load all
	function load($search, $categoryid, $parentid, $type, $isprivate, $order, $summary, $pagenumber, $pagesize, $iscache = false)
	{
	   if(Feature_Cache == 1 && $iscache) // cache enabled
	   {
	     $cache = new MyMemcahe();
		 $key = categoriesmgt::generate_key("catlst_", $search, $categoryid, $parentid, $type, $isprivate, $order, $summary, $pagenumber, $pagesize);
	     $lst = $cache->Get($key); // fetch from cache
		 if($lst != NULL)
	        return  $lst; //;
		 else
		 {
			 $lst = categoriesmgt::load_nocache($search, $categoryid, $parentid, $type, $isprivate, $order, $summary, $pagenumber, $pagesize);
			 // cache output
			 $cache->Add($key, $lst);
			 return $lst;
		 }
	   }
	   else
	   {

		  // cache not enabled
		  $lst = categoriesmgt::load_nocache($search, $categoryid, $parentid, $type, $isprivate, $order, $summary, $pagenumber, $pagesize);
		  return $lst;
	   }
	}
	
	// non cache version of load categories
	function load_nocache($search, $categoryid, $parentid, $type, $isprivate, $order , $summary, $pagenumber, $pagesize)
	{
		$startindex = ($pagenumber - 1) * $pagesize;
		$fields = "*";
		if($summary)
		   $fields = "categoryid,parentid, categoryname,term,records,mode,level";
		$query = "SELECT " . $fields . " from categories";
		
		$bind = array();
		if($categoryid > 0)
		{			
			$query .= " where categoryid=:categoryid";
			$bind['categoryid'] = $categoryid;
		}
		else
		{
			$privatestr = "";
            if ($isprivate != 2)
			{
                $privatestr = " AND isprivate=:isprivate";
				$bind['isprivate'] = $isprivate;
			}
            $searchstr = "";
            if ($search != "")
            {
                $searchstr = " (categoryname like :search OR term like :search)";
				$bind['search']= '%'.$search.'%';
				
                $query .= " where" . $searchstr . "" . $privatestr . " AND type=:type order by " . $order;
				$bind['type'] = $type;
            }
            else
            {
				$parentstr = "";
				if($parentid > -1)
				{
				  $parentstr = " AND parentid=:parentid";
				  $bind['parentid'] = $parentid;
				}
                $query .= " where type=:type" . $privatestr . "" . $parentstr . " order by " . $order;
				$bind['type'] = $type;
            }
		}
		if($pagenumber > 0)
		   $query .= " limit " . $startindex . "," . $pagesize;
		   
		$db = new DB;
        $rec = $db->smartQuery(array(
        'sql' => $query,
		'par' => $bind,
        'ret' => 'obj'
         ));
		 
		 $records = array();
 		 while($r = $rec->fetch(PDO::FETCH_OBJ))
		 {	
		 	 $records[] = $r;
		 }
		 return $records;
	}
		
	
	// pagenumber 0 -> load all
	function countcategories($search, $categoryid, $parentid, $type, $isprivate){
		$query = "SELECT count(categoryid) as total from categories";
		$bind = array();
		if($categoryid > 0)
		{			
			$query .= " where categoryid=:categoryid";
			$bind['categoryid'] = $categoryid;
		}
		else
		{
			$privatestr = "";
            if ($isprivate != 2)
			{
                $privatestr = " AND isprivate=:isprivate";
				$bind['isprivate'] = $isprivate;
			}
            $searchstr = "";
            if ($search != "")
            {
                $searchstr = " (categoryname like :search OR term like :search)";
				$bind['search']= '%'.$search.'%';
				
                $query .= " where" . $searchstr . "" . $privatestr . " AND type=:type";
				$bind['type'] = $type;
            }
            else
            {
                $parentstr = "";
				if($parentid > -1)
				{
				  $parentstr = " AND parentid=:parentid";
				  $bind['parentid'] = $parentid;
				}
                $query .= " where type=:type" . $privatestr . "" . $parentstr;
				$bind['type'] = $type;
            }
		}
			   
		$db = new DB;
		$total = $db->smartQuery(array(
		'sql' => $query,
		'par' => $bind,
		'ret' => 'col'
		 ));
		 return $total;

	}
	
	// generate unique cache key for load / count operation
	function generate_key($ref, $search, $categoryid, $parentid, $type, $isprivate, $order , $summary, $pagenumber, $pagesize)
	{
		$key = $ref . "" . $search . "" . $categoryid . "" . $parentid . "" . $type . "" . $isprivate;
		if($order != "")
			$key .= $order;
		if($pagenumber > 0)
		  $key .= $pagenumber;
		$key .= $summary;
		if($pagesize > 0)
		  $key .= $pagesize;
		
		$key =  preg_replace('/[^\w\._]+/', '_', $key); // remove illigal characters
	    return $key;
	}
		
	public $level = array();
	function preparelevel($categoryid)
	{
		$this->level[] = $categoryid;
		categoriesmgt::generate($categoryid);
		return implode('.', array_reverse($this->level));
		
	}
	function generate($categoryid)
	{
		$parentid = categoriesmgt::get_field_value($categoryid, "parentid");
		if($parentid == $categoryid)
		{
			$this->level[] = $parentid;
			return;
		}
		if($parentid > 0)
		  $this->level[] = $parentid;
		if (categoriesmgt::has_child($parentid, 2))
		{
			categoriesmgt::generate($parentid);
		} 
	}
  
}
?>