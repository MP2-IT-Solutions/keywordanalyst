<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   keywordanalyst
 * @author    Georg Lugmayr
 * @license   GNU/LGPL
 * @copyright Georg Lugmayr
 */


/**
 * Namespace
 */
namespace keywordanalyst;


/**
 * Class keywordAnalyst
 *
 * @copyright  Georg Lugmayr
 * @author     Georg Lugmayr
 * @package    Devtools
 */
class keywordAnalyst extends \BackendModule
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_keywordanalyst';
	

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		
		// GENERATE DETAIL-PAGE
		if($this->Input->get('details'))
		{
			$objPage = $this->Database->prepare("SELECT id, alias, title, pageTitle, description FROM tl_page WHERE id=?")
								  ->limit(1)
								  ->execute($this->Input->get('details'));
								  
			$data = $this->getDisplayedText($this->Input->get('details'),$objPage->pageTitle,$objPage->alias,$objPage->description,true);
			
			if($objPage->pageTitle == "")
			{
				$objPage->pageTitle = $objPage->title;
			}
			$this->Template->title = $objPage->pageTitle;
			$this->Template->alias = $objPage->alias;
			$this->Template->description = $objPage->description;
			$this->Template->keywords = $data[0];
			$this->Template->text = $data[1];
			$this->Template->titleArr = explode(" ",strtolower($objPage->pageTitle));
			$this->Template->aliasArr = explode("-",strtolower($objPage->alias));
			$this->Template->descrArr = explode(" ",preg_replace("/[^a-z0-9 äöüß]/usi", "", strip_tags(strtolower($objPage->description))));
		}
		
		//GENERATE PAGETREE
		else
		{
			$objRoot = $this->Database->prepare("SELECT id FROM tl_page WHERE type=? ORDER BY sorting")
								  ->execute("root");
			while($objRoot->next())
			{
				$html .= $this->renderPagetree($objRoot->id,-20,false,false);
			}			
			
			$this->Template->pagetree = $html;
		}
	}
	
	/**
	 * Recursively render the pagetree
	 * @param int
	 * @param integer
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	protected function renderPagetree($id, $intMargin, $protectedPage=false, $blnNoRecursion=false)
	{
		static $session;
		$session = $this->Session->getData();

		$flag = substr($this->strField, 0, 2);
		$node = 'tree_' . $this->strTable . '_' . $this->strField;
		$xtnode = 'tree_' . $this->strTable . '_' . $this->strName;

		// Get the session data and toggle the nodes
		if (\Input::get($flag.'tg'))
		{
			$session[$node][\Input::get($flag.'tg')] = (isset($session[$node][\Input::get($flag.'tg')]) && $session[$node][\Input::get($flag.'tg')] == 1) ? 0 : 1;
			$this->Session->setData($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)'.$flag.'tg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objPage = $this->Database->prepare("SELECT id, alias, pageTitle, description, type, protected, published, start, stop, hide, title FROM tl_page WHERE id=?")
								  ->limit(1)
								  ->execute($id);
		
		
		// Return if there is no result
		if ($objPage->numRows < 1)
		{
			return '';
		}

		$return = '';
		$intSpacing = 20;
		$childs = array();

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			$objNodes = $this->Database->prepare("SELECT id FROM tl_page WHERE pid=? ORDER BY sorting")
									   ->execute($id);

			if ($objNodes->numRows)
			{
				$childs = $objNodes->fetchEach('id');
			}
		}

		$return .= "\n    " . '<li class="'.(($objPage->type == 'root') ? 'tl_folder' : 'tl_file').'" onmouseover="Theme.hoverDiv(this, 1)" onmouseout="Theme.hoverDiv(this, 0)" onclick="Theme.toggleSelect(this)"><div class="tl_left" style="padding-left:'.($intMargin + $intSpacing).'px">';
		
		// add legend
		if($objPage->type == 'root')
		{
			$return .= '<div class="oneWordWrapper"><span class="rank1">1</span><span class="rank2">2</span><span class="rank3">3</span></div>';
		}
		$folderAttribute = 'style="margin-left:20px"';
		$session[$node][$id] = is_numeric($session[$node][$id]) ? $session[$node][$id] : 0;
		$level = ($intMargin / $intSpacing + 1);
		$blnIsOpen = true;

		if (!empty($childs))
		{
			$folderAttribute = '';
			$img = $blnIsOpen ? 'folMinus.gif' : 'folPlus.gif';
			$alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			$return .= '<a href="'.$this->addToUrl($flag.'tg='.$id).'" title="'.specialchars($alt).'" onclick="return AjaxRequest.togglePagetree(this,\''.$xtnode.'_'.$id.'\',\''.$this->strField.'\',\''.$this->strName.'\','.$level.')">'.\Image::getHtml($img, '', 'style="margin-right:2px"').'</a>';
		}

		// Set the protection status
		$objPage->protected = ($objPage->protected || $protectedPage);

		// Add the current page
		if (!empty($childs))
		{
			$return .= \Image::getHtml($this->getPageStatusIcon($objPage), '', $folderAttribute).' <span title="'.specialchars($objPage->title . ' (' . $objPage->alias . $GLOBALS['TL_CONFIG']['urlSuffix'] . ')').'">'.(($objPage->type == 'root') ? '<strong>' : '').$objPage->title.(($objPage->type == 'root') ? '</strong>' : '').'</span></div> <div class="tl_right">';
		}
		else
		{
			$return .= \Image::getHtml($this->getPageStatusIcon($objPage), '', $folderAttribute).' '.(($objPage->type == 'root') ? '<strong>' : '').$objPage->title.(($objPage->type == 'root') ? '</strong>' : '').'</div> <div class="tl_right">';
		}

		
		if($objPage->type != 'root')
		{
			if($objPage->pageTitle == "")
			{
				$noEmptyTitle = $objPage->title;
			}
			else
			{
				$noEmptyTitle = $objPage->pageTitle;
			}
			
			$return .= $this->getDisplayedText($id,$noEmptyTitle,$objPage->alias,$objPage->description,false);
		}
		$return .= '</div>';
		$return .= '<div style="clear:both"></div></li>';

		// Begin a new submenu
		if (!empty($childs) && ($blnIsOpen || $this->Session->get('page_selector_search') != ''))
		{
			$return .= '<li class="parent" id="'.$node.'_'.$id.'"><ul class="level_'.$level.'">';

			for ($k=0, $c=count($childs); $k<$c; $k++)
			{
				$return .= $this->renderPagetree($childs[$k], ($intMargin + $intSpacing), $objPage->protected);
			}

			$return .= '</ul></li>';
		}

		return $return;
	}
	
	
	
	/**
	 * Get all displayed text in articles (incl. title,alias,description)
	 * @param int
	 * @param integer
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	protected function getDisplayedText($pageid,$title,$alias,$description,$isDetail)
	{
		
		$objArticles = $this->Database->prepare("SELECT id FROM tl_article WHERE pid=? AND published=?")->execute($pageid,1);
									   
		while ($objArticles->next())
		{	
			$contentid = $objArticles->id; 
			$objElements = $this->Database->prepare("SELECT text, caption, html, headline, listitems, tableitems, code, mooHeadline, linkTitle FROM tl_content WHERE pid=? AND invisible!=? ORDER BY sorting")->execute($contentid,1);
									   
			while ($objElements->next())
			{
				
					$headline = unserialize($objElements->headline);
					
					$text .= " ".$headline['value'];
					$text .= " ".$objElements->text; 
					$text .= " ".$objElements->caption; 
					$text .= " ".$objElements->html; 
					
					$listitems = unserialize($objElements->listitems);
					if($listitems)
					{
						foreach($listitems as $key => $value)
						{
							$text .= " ".$value; 
						}
					}
					
					$tableitems = unserialize($objElements->tableitems);
					if($tableitems)
					{
						foreach($tableitems as $key )
						{
							foreach($key as $nr => $value)
							$text .= " ".$value; 
						}
					}
					
					$text .= " ".$objElements->code; 
					$text .= " ".$objElements->mooHeadline; 
					$text .= " ".$objElements->linkTitle; 
			}					   
		}
		
		// add title alias & description
		$text .= " ".$title." ".str_replace("-"," ",$alias)." ".$description;
		
		//clean up
		$htmlspaces = array("</p>","</div>","</li>","</a>","[nbsp]","</h1>","</h2>","</h3>","</h4>","</h5>","</h6>","</td>","<hr>");
		$text = str_replace($htmlspaces," ",$text);
		$text = preg_replace("/[^a-z0-9 äöüß]/usi", "", strip_tags(strtolower($text)));
		
		// render page for details/pagetree
		if($isDetail)
		{
			$keywordsArray = str_word_count($text, 1, "ÄäÜüÖöß");
			$wordCount = str_word_count($text, 0, "ÄäÜüÖöß");
			return array($this->keywordSorting($keywordsArray,$wordCount),$text);
		}
		else
		{
			return $this->analyzeTextTop3($text,$title,$pageid);
		}
	}
	
	
	
	/**
	 * calculate keywords top 3
	 * @param int
	 * @param integer
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	protected function analyzeTextTop3($text,$title,$pageid)
	{	
		$rankString = "";
		$rankStart = 1;
		$keywordsArray = str_word_count($text, 1, "ÄäÜüÖöß");
		$wordCount = str_word_count($text, 0, "ÄäÜüÖöß");
		
		// sort all keywords
		$keywordArray = $this->keywordSorting($keywordsArray,$wordCount);
		
		// generate link for detailpage
		$rankString .= '<a class="info" href="contao/main.php?do=keywordanalyst&popup=1&details='.$pageid.'" title="display all keywords" style="padding-left:3px" onclick="Backend.openModalIframe({\'width\':765,\'title\':\'Keywordanalyst for '.$title.'\',\'url\':this.href});return false"><img src="system/themes/default/images/show.gif" alt="Quellelement bearbeiten" style="vertical-align:top"></a>';
		
		$rankString .= '<div class="oneWordWrapper">';
		$rankStart = 1;
		
		if($keywordArray[0])
		{
			foreach($keywordArray[0] as $oneword => $keyword)
			{
				if($rankStart>3)
				{
					break;
				}
				
				$rankString .= "<span class='rank".$rankStart."'>".$oneword."</span>";
				$rankStart++;
			}
		}
		
		$rankString .= '</div>';
		
		return $rankString;
		
	}
	
	
	/**
	* calculate keywords top 3
	*
		Author: SEO Review Tools
		website: http://www.seoreviewtools.com
		
		Script Name: Keyword density checker 
		Version: 1.0
		Updates: http://www.seoreviewtools.com/multi-keyword-density-checker-php-script/
	*
	*/
	protected function keywordSorting($keywordsArray, $wordCount)
	{
		$keywordsSorted0 = ''; // 1 word match 
		$keywordsSorted1 = ''; // 2 word phrase match 
		$keywordsSorted2 = ''; // 3 word phrase match 
		$keywordsSorted3 = ''; // 4 word phrase match 
		
		$stopWordsDE = explode(",","aber,als,am,an,auch,auf,aus,bei,bin,bis,bist,da,dadurch,daher,darum,das,daß,dass,dein,deine,dem,den,der,des,dessen,deshalb,die,dies,dieser,dieses,doch,dort,du,durch,ein,eine,einem,einen,einer,eines,er,es,euer,eure,für,hatte,hatten,hattest,hattet,hier,hinter,ich,ihr,ihre,im,in,ist,ja,jede,jedem,jeden,jeder,jedes,jener,jenes,jetzt,kann,kannst,können,könnt,machen,mein,meine,mit,muß,mußt,musst,müssen,müßt,nach,nachdem,nein,nicht,nun,oder,seid,sein,seine,sich,sie,sind,soll,sollen,sollst,sollt,sonst,soweit,sowie,und,unser,unsere,unter,vom,von,vor,wann,warum,was,weiter,weitere,wenn,wer,werde,werden,werdet,weshalb,wie,wieder,wieso,wir,wird,wirst,wo,woher,wohin,zu,zum,zur,über,nbsp,bzw,etc,zb");
		$stopWordsEN = explode(",","a,about,above,after,again,against,all,am,an,and,any,are,aren't,as,at,be,because,been,before,being,below,between,both,but,by,can't,cannot,could,couldn't,did,didn't,do,does,doesn't,doing,don't,down,during,each,few,for,from,further,had,hadn't,has,hasn't,have,haven't,having,he,he'd,he'll,he's,her,here,here's,hers,herself,him,himself,his,how,how's,i,i'd,i'll,i'm,i've,if,in,into,is,isn't,it,it's,its,itself,let's,me,more,most,mustn't,my,myself,no,nor,not,of,off,on,once,only,or,other,ought,our,ours,ourselves,out,over,own,same,shan't,she,she'd,she'll,she's,should,shouldn't,so,some,such,than,that,that's,the,their,theirs,them,themselves,then,there,there's,these,they,they'd,they'll,they're,they've,this,those,through,to,too,under,until,up,very,was,wasn't,we,we'd,we'll,we're,we've,were,weren't,what,what's,when,when's,where,where's,which,while,who,who's,whom,why,why's,with,won't,would,wouldn't,you,you'd,you'll,you're,you've,your,yours,yourself,yourselves");	
		for ($i = 0; $i < count($keywordsArray); $i++)
		{
			if(in_array($keywordsArray[$i],$stopWordsDE) || in_array($keywordsArray[$i],$stopWordsEN))
			{
				continue;
			}

			// 1 word phrase match 
			if ($i+0 < $wordCount)
			{
				$keywordsSorted0 .= $keywordsArray[$i].',';			
			} 
			// 2 word phrase match 
			if ($i+1 < $wordCount)
			{
				$keywordsSorted1 .= $keywordsArray[$i].' '.$keywordsArray[$i+1].',';
			} 
			// 3 word phrase match 
			if ($i+2 < $wordCount)
			{
				$keywordsSorted2 .= $keywordsArray[$i].' '.$keywordsArray[$i+1].' '.$keywordsArray[$i+2].',';			
			} 
			// 4 word phrase match 
			if ($i+3 < $wordCount)
			{
				$keywordsSorted3 .= $keywordsArray[$i].' '.$keywordsArray[$i+1].' '.$keywordsArray[$i+2].' '.$keywordsArray[$i+3].',';
			} 
		}
		
		for ($i = 0; $i <= 3; $i++)
		{
			// Build array form string. 
			${'keywordsSorted'.$i} = array_filter(explode(',', ${'keywordsSorted'.$i}));			
			${'keywordsSorted'.$i} = array_count_values(${'keywordsSorted'.$i});
			asort(${'keywordsSorted'.$i});
			arsort(${'keywordsSorted'.$i});	
			
			foreach (${'keywordsSorted'.$i} as $key => $value)
			{
				${'keywordsSorted'.$i}[$key] = array($value, number_format((100 / $wordCount * $value),2));		
			}
		}	
		
		return array($keywordsSorted0, $keywordsSorted1, $keywordsSorted2, $keywordsSorted3);
	}
	
}
