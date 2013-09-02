PHP vCategory Script
====================

Fatest way to generate almost any type of multi heirarchy directories and category listings in your php applications.

Database Table Structure
========================

<pre>
CREATE TABLE `categories` (
  `categoryid` smallint(6) NOT NULL AUTO_INCREMENT,
  `categoryname` varchar(100) NOT NULL,
  `parentid` smallint(6) NOT NULL DEFAULT '0',
  `added_date` datetime DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `isprivate` tinyint(4) NOT NULL DEFAULT '0',
  `mode` tinyint(4) NOT NULL DEFAULT '0',
  `term` varchar(50) DEFAULT NULL,
  `picturename` varchar(100) DEFAULT 'none',
  `description` text,
  `records` int(11) NOT NULL DEFAULT '0',
  `level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`categoryid`)
) ENGINE=MyISAM AUTO_INCREMENT=659 DEFAULT CHARSET=utf8;</pre>

Level Field Data Structure
========================

Data must be stored in the following pattern in level field. It's the core field for generating multi level parent child heirarchy instead of using recursive approach.

level = [cid].[parentid].[parent parentid].[etc]

level = 232.332.21.4.0

Example Code
=======================
Sample code for generating level at time of adding / editing category information

<pre>
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
}</pre>

For more examples and documentation of vCategory Script visit http://www.mediasoftpro.com/php/vcategory/
