<?php
/**
 *
 *  Copyright (C) 2017
 *
 *
 *  @who	   	PAJ
 *  @info   	paj@gaiterjones.com
 *  @license    blog.gaiterjones.com
 *
 *
 *
 */
namespace PAJ\Application\AmazonDev\Alexa\Intent;
use PAJ\Application\AmazonDev\Alexa\GetIntent;

/**
 * ALEXA INTENT HELPER CLASS
 *
 * @extends GetIntent
 */
class Helper extends GetIntent {


	public function __construct() {


	}


	public function getNewFact($_table,$_count=1,$_exclude=array('0'),$_search=false)
	{
		$_timeZone=false;
		$_DBServer='medazzaland1_mysql_1';
		$_DBUser='applicationsql';
		$_DBPass='NPfvJePAOuz5fDZV';
		$_cacheNameSpace='ALEXA-'.$_table;
		$_dbName='paj_amazon_alexa_skill';
		$_useCacheforQuery=false;
		$_cacheTTL=172800;

		$_numRows=0;
		$_queryResult=false;

		$_query='
				SELECT  *
				FROM    (
						SELECT  *
						FROM    '. $_table. '
						WHERE 	id NOT IN ('. implode(',',$_exclude). ') AND type="fact"'. ($_search ? ' AND (subject LIKE "%'. strtolower(substr($_search, 0, -2)). '%")' : ''). '
						ORDER BY
								rand()
						LIMIT '. $_count. '
						) q
				ORDER BY
						timestamp DESC;';

		try
		{
			$_obj=new \PAJ\Library\DB\MYSQL\QueryAllRows($_query,$_cacheNameSpace,$_dbName,$_useCacheforQuery,$_cacheTTL,$_timeZone,$_DBServer,$_DBUser,$_DBPass);
				$_queryResult=$_obj->get('queryresult');
					$_numRows=$_obj->get('queryrows');
						unset($_obj);


		}

		// catch errors
		//
		catch (\Exception $e)
		{
			return false;
		}

		$this->set('success',true);
		$this->set('output',array(
				'getNewFact' =>array(
					'queryresult' => $_queryResult,
					'class' => (new \ReflectionClass($this))->getShortName(),
					'search' => strtolower($_search),
					'count' => $_numRows
				)
		));

		return $_queryResult;
	}

	public function getSubjects($_table)
	{
		$_timeZone=false;
		$_DBServer='medazzaland1_mysql_1';
		$_DBUser='applicationsql';
		$_DBPass='NPfvJePAOuz5fDZV';
		$_cacheNameSpace='ALEXA-'.$_table;
		$_dbName='paj_amazon_alexa_skill';
		$_useCacheforQuery=true;
		$_cacheTTL=172800;

		$_numRows=0;
		$_queryResult=false;

		$_query="SELECT subject
					FROM ". $_table. ";";

		try
		{
			$_obj=new \PAJ\Library\DB\MYSQL\QueryAllRows($_query,$_cacheNameSpace,$_dbName,$_useCacheforQuery,$_cacheTTL,$_timeZone,$_DBServer,$_DBUser,$_DBPass);
				$_queryResult=$_obj->get('queryresult');
					$_numRows=$_obj->get('queryrows');
						unset($_obj);


			$_data=array();
			foreach ($_queryResult as $_row)
			{
				$_data[]=$_row['subject'];
			}

		}
		// catch errors
		//
		catch (\Exception $e)
		{
			return false;
		}


		$this->set('success',true);
		$this->set('output',array(
				'getSubjects' =>array(
					'queryresult' => $_queryResult,
					'class' => (new \ReflectionClass($this))->getShortName(),
					'count' => $_numRows
				)
		));

		return array_unique($_data);
	}

}
