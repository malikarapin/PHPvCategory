<?php
/**
 * vskCategory Script
 * 
 * This class is responsible for processing and generating
 * almost any type of category dropdown, array, or ul / li listings 
 * 
 * @author irfan (Muhammad Irfan, irfan.nettech@gmail.com)
 * @package PHPvCategory
 * @license New BSD 
 * @version 1.0
 */
 

include_once("bll.php");

class vskCategory
{
	// box template
	/* Output Type 
	--> 0: return complete panel
	--> 1: return div output with ul
	--> 2: return ul li listing
	--> 3: return array
	--> 4: return dropdown list */
	public $outputType; 
	/* heading title
	--> Output Type = 0 only */
	public $headingTitle;
	/* parents only links */
	public $isParents;
	/* Load all links 
	if false then totalLinks = {n} will call */
	public $isAll;
	/* total links
	--> if isAll proper is false
	--> and total links > 0 */
	public $totalLinks;
	/* more category links
	--> if isAll is false
	--> total links <= all available links */
	public $moreLinkUrl;
	public $moreLinkText;
	public $moreLinkTooltip;
	public $moreLinkCss;
	/* show counter with links*/
	public $showCounter;
	/* total Columns
	--> > 0 (multi column script enabled) */
	public $totalColumns;
	
	// css property
	public $css;
	public $parentCss; // parent ul
	public $childCss; // child ulr
	public $liCss;
	public $parentliCss;
	public $liActiveCss;
	public $linkCss;
	public $linkActiveCss;
	public $parentLinkCss;
	
	// option css
	public $panelCss;
	public $panelHeadingCss;
	// attributes
	public $parentid;
	public $mode;
	public $order;
	/* link url 
	-> e.g index.php?cat=[CT] -> term
	-> e.g index.php?cat=[CN] -> categoryname
	-> eg. index.php?cat=[CID] -> categoryid */
	public $url;
	public $linkAttr; // additional attributes
	public $parentlinkAttr;  // additional attributes
	/* type specific
	--> 0: -> videos
	--> 5: -> photos etc */
	public $type;
	/* Advance */
	public $totalParents;
	public $totalChilds;
	/* customize display
	--> Custom Value e.g <span>[CN]</span>[CT],[CID] where [CN] -> category name, [CT] -> term and [CID] -> category id */
	public $customValue;
	public $parentCustomValue; // customize value for parent item only
	/* repeat direction 
	-> if total columns > 0
	-> 0: vertical
	-> 1: horizontal */
	public $repeatDirection;
	/* column width
	-> if total columns > 0 */
	public $columnWidth;
	/* advance css classes 
	-> for directory listings */
	
	/* load selected parent categories only */
	public $loadParentCategories;
	/* max level to go
	-> 0: no limit */
	public $maxlevels;
	/* cache listing */
	public $isCache;
	
	function __construct()
	{
		$this->outputType = 0;
		$this->headingTitle = "";
		$this->isParents = false;
		$this->isAll = false;
		$this->totalLinks = 0;
		$this->moreLinkUrl = "";
		$this->moreLinkText = "";
		$this->moreLinkTooltip = "";
		$this->moreLinkCss = "";
		$this->showCounter = false;
		$this->totalColumns = 1;
		// css
		$this->css = "";
		$this->liCss = "";
		$this->parentliCss = "";
		$this->liActiveCss = "";
		$this->linkCss = "";
		$this->parentLinkCss = "";
		$this->linkActiveCss = "";
		$this->panelCss = "";
		$this->panelHeadingCss = "";
		// attributes
		$this->parentid = 0;
		$this->mode = 0;
		$this->order = "level asc"; // for proper herarchy don't use another level refernce
		$this->type = 0;
		$this->url = "#";
		$this->linkAttr = "";
		$this->parentlinkAttr = "";
		// advance
		$this->totalParents = 0;
		$this->totalChilds = 0;
		$this->customValue = "";
		$this->parentCustomValue = "";
		$this->repeatDirection = 0;
		$this->columnWidth = "";
		$this->parentCss = "";
		$this->childCss = "";
		$this->loadParentCategories = false;
		$this->maxlevels = 0;
		$this->isCache = false;
	}
	
	public function Process()
	{		
		$rec = $this->fetchRecords();
		if($this->totalColumns == 1) {
			// single column
			if($this->outputType == 3)
			  return $rec;
			else if($this->outputType == 2)
			  return $this->prepareUlListing($rec);
			else if($this->outputType == 1)
			  return $this->prepareUlListing($rec, true);
			else if($this->outputType == 0)
			  return $this->preparePanel($rec);
		}
		else {
			// multiple column
		    return $this->prepareMultiColumn($rec);
		}
	}
	
	private function prepareMultiColumn($obj)
	{
		$listing = "";
		if($this->columnWidth == "")
		{
			// calculate column width
			switch($this->totalColumns)
			{
				case 2:
				   $this->columnWidth = "col-lg-6";
				   break;
				case 3:
				   $this->columnWidth = "col-lg-4";
				   break;
				case 4:
				   $this->columnWidth = "col-lg-3";
				   break;
				case 6:
				   $this->columnWidth = "col-lg-2";
				   break;
				case 12:
				   $this->columnWidth = "col-lg-1";
				   break;
			}
		}
		$index = 0;
		$iindex = 1;
		$cparents = $this->cParents();
		$rdiv = true;
		if($this->isParents)
		 $rdiv = false;
		if($this->repeatDirection == 0)
		{
		    // vertical	
			$columnPaters = (int)ceil($cparents / $this->totalColumns);
			$this->totalParents = 1;
			for ($i = 0; $i < $this->totalColumns; $i++)
			{
				//$iindex = 0;
				$listing .= "<div class=\"" . $this->columnWidth . "\">\n";
				for($j = 0; $j < $columnPaters; $j++)
				{
					$listing .= $this->prepareUlListing($obj, $rdiv, $iindex);
					$iindex++;
				}
				
				$listing .= "</div>\n";
				$index = $index + $columnPaters;
			}
		}
		else if($this->repeatDirection == 1)
		{
			// horizontal direction
			$this->totalParents = 1;
			//$listing .= "<div class=\"row\">\n";
			$counter = 0;
			$index = 1;
			$this->totalParents = 1;
			for ($i = 0; $i < $cparents; $i++)
			{
			   if($counter >= $this->totalColumns)
			   {
				   $listing .= "</div><div class=\"row\">\n";
				   $counter = 0;
			   }
			   $listing .= "<div class=\"" . $this->columnWidth . "\">\n";
			   $listing .= $this->prepareUlListing($obj, $rdiv, $index);
			   $listing .= "</div>\n";
			   $index++;
			   $counter++;
			}
			//$listing .= "</div>\n";
		}
		return $listing;
	}
	private function preparePanel($obj)
	{
		$listing = "<div" . $this->prepareCss($this->panelCss) . ">\n";
		if($this->headingTitle != "")
		{			  
			$listing .= "<div" . $this->prepareCss($this->panelHeadingCss) . ">\n";
			$listing .= $this->headingTitle;
			$listing .= "</div>\n";
		}
		$rdiv = false;
		if($this->css != "")
		  $rdiv = true;
		$listing .= $this->prepareUlListing($obj, $rdiv);
		$listing .= "</div>\n"; 
		return $listing;
	}
	
	private function prepareUlListing($obj, $rdiv = false, $pindex = 0)
	{
		if(count($obj) == 0)
		  return "";
		
		$pl = 0;
		$lev = 0;
		$listing = "";
		if($rdiv)
  		  $listing .= "<div" . $this->prepareCss($this->css) . ">\n";
		$listing .= "<ul" . $this->prepareCss($this->parentCss) .">\n";
		$isP = true;
		$pCss = $this->prepareCss($this->parentliCss);
		if($pCss == "")
		   $pCss = $this->prepareCss($this->liCss);
		$plink = $this->prepareCss($this->parentLinkCss);
		if($plink == "")
		  $plink =  $this->prepareCss($this->linkCss);
         
		$counter = 0;
		$pCounter = 0;
		$cCounter = 0;
		$schilds = false;
		$skip = true;
		
		$ccParents = 0;
		$ccCounter = 0;
		$skip = false;
		if($pindex > 0)
		  $skip = true;
		foreach($obj as $itm)
		{		  
		  if($this->totalParents > 0)
		  {
			  if($pCounter >= $this->totalParents)
     		     $schilds = true;
		  }
		  $lev = substr_count($itm->level, ".");
		  if($lev == 0)
		  {
			  if($schilds) continue;
			  else
			  {
				   $ccParents++;
			       if($ccParents >= $pindex)
			         $ccCounter++;
			  }
		  }
		  if($this->maxlevels != 0)
		  {
			  if($lev > $this->maxlevels)
			    continue;
		  }
		  if($this->totalParents > 0)
		  {
			  if($ccCounter > $this->totalParents)
			    $skip = true;
			  else if($ccCounter > 0)
			    $skip = false;
		  }
		  if($skip) continue;
		  if($lev != $pl)
		  {			  
			  $cCounter = 0;
			  if($lev > $pl)
			  {
			    $listing .= "<ul" . $this->prepareCss($this->childCss) .">\n<li" . $this->prepareCss($this->liCss) . ">";
                $isP = false;
			  }
			  else
			  {
			    $listing .= "</li>\n</ul>\n</li>\n<li>";
				if($lev == 0) $isP = true;
			  }
		  } else {
			  $cCounter++;
			  if($this->totalChilds > 0)
		      {
			    if($cCounter >= $this->totalChilds)
			       continue;
		      } 
			  if($counter > 0)
			      $listing .= "</li>\n";
			  if($isP)
			     $listing .= "<li" . $pCss . ">";
			  else
			    $listing .= "<li" . $this->prepareCss($this->liCss) . ">";
		  }
		  
   	      $citem = $this->prepareCategoryName($itm->categoryname, $isP);
		  if($this->showCounter)
		    $citem .= " (" . $itm->records . ")";
		  $lcss = "";
		  $lattr = "";
		  if(!$isP)
		  {
		     $plink = $this->prepareCss($this->linkCss);
			 if($this->linkAttr != "")
			    $lattr = " " . $this->linkAttr;
		  }
		  else
		  {
			  $plink = $this->prepareCss($this->parentLinkCss);
			  if($plink == "")
			    $plink = $this->prepareCss($this->linkCss);
			  if($this->parentlinkAttr != "")
			    $lattr = " " . $this->parentlinkAttr;
		  }
		 
		  $listing .= "<a" . $plink . "" . $lattr . " href=\"" . $this->prepareUrl($itm->categoryid, $itm->categoryname, $itm->term) . "\">" . $citem . "</a>\n";	
		 		 
		   $pl = $lev;
		   $counter++;
		}
		$listing .= "</li>";
		if(!$this->isAll && $this->moreLinkUrl != "")
			$listing .= "<li><a" . $this->prepareCss($this->moreLinkCss) . " href=\"" . $this->moreLinkUrl . "\" title=\"" . $this->moreLinkTooltip . "\">" . $this->moreLinkText . "</a></li>\n";
		$listing .= "</ul>\n";
		if($rdiv)
		  $listing .= "</div>\n";
		  
		
		return $listing;
	}
	private function fetchRecords()
	{
		$search = ""; // no search filter
		$categoryid = 0; // obsolete
		$isprivate = 0; // only public
		$summary = true; // fetch short info
		$pagenumber = 0; // unlimted
		if(!$this->isAll && $this->totalLinks > 0)
		   $pagenumber = 1;
		if($this->isParents)
		  $this->parentid = 0;
		$cats = new categoriesmgt();
		return $cats->load($search, $categoryid, $this->parentid, $this->type, $isprivate, $this->order, $summary, $pagenumber, $this->totalLinks, $this->isCache);
	}
	
	private function cParents()
	{
		$search = ""; // no search filter
		$categoryid = 0; // obsolete
		$isprivate = 0; // only public
		$parentid = 0;
		$cats = new categoriesmgt();
		return $cats->countcategories($search, $categoryid, $parentid, $this->type, $isprivate);
	}
	
	private function prepareCss($css) {
		if($css != "")
		  return " class=\"" . $css . "\"";
		else
		  return "";
	}
	private function prepareCategoryName($cn, $isP)
	{
		$val = $cn;
		if($isP)
		{
			if($this->parentCustomValue != "")
			   $val = preg_replace("/\[CN\]/", $cn, $this->parentCustomValue);
		}
		else
		{
		    if($this->customValue != "")
		       $val = preg_replace("/\[CN\]/", $cn, $this->customValue);
		}
		return $val;
	}
	private function prepareUrl($cid, $cn, $ct)
	{
		$url = "#";
		if($this->url != "")
		{
			$url = preg_replace("/\[CT\]/", $this->replacespacewithhyphin($ct), $this->url);
			$url = preg_replace("/\[CN\]/", $this->replacespacewithhyphin($cn), $url);
			$url = preg_replace("/\[CID\]/", $cid, $url);
		}
		return $url;
	}
	private function replacespacewithhyphin($input)
    {
        // replace all  spaces with hyphin
        $str = preg_replace("/\s/", "-", $input); 
		$str = preg_replace("/[\-]+/", "-", $str); 
        // remove special characters
        return strtolower(preg_replace("/[^0-9a-zA-Z-_]+/", "", $str)); 
		//return preg_replace("/[^\w\._]+/","",$str);
    } 
}
?>