<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2008 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/

require_once('ForumsUrl.class.php');
require_once('ContentUrl.class.php');
require_once('FileStorageUrl.class.php');

/**
* UrlRewrite
* Class for rewriting pretty urls.
* @access	public
* @author	Harris Wong
* @package	UrlRewrite
*/
class UrlRewrite  {
	// local variables
	var $path;		//the path of this script
	var $filename;	//script name
	var $query;		//the queries of the REQUEST
	var $isEmpty;	//true if path, filename, and query are empty

	// constructor
	function UrlRewrite($path, $filename, $query) {
		if ($path=='' && $filename=='' && $query==''){
			$this->isEmpty = true;
		} else {
			$this->isEmpty = false;
		}
		$this->path = $path;
		$this->filename = $filename;
		$this->query = $query;
	}

	// public 
	function setRule($rule) {
		echo 'parent setting the rule';
		$this->rule = $rule;
	}

	// protected
	function getRule($rule_key) {
		return 'i am the parent: '.$rule;
	}

	// public
	//deprecated
	function redirect(){
		//redirect to that url.
		return '/'.$this->getPage();
	}

	//public
	function parsePrettyQuery(){
		global $_config;
		$result = array();

		//return empty array if query is empty
		if (empty($this->query)){
			return $result;
		}

		//if course_dir_name is disabled from admin. 
		if ($_config['pretty_url']==0){
			return $this->query;
		}

		//If the first char is /, cut it
		if (strpos($this->query, '/') == 0){
			$query_parts = explode('/', substr($this->query, 1));
		} else {
			$query_parts = explode('/', $this->query);
		}

		//assign dynamic pretty url
		foreach ($query_parts as $array_index=>$key_value){
			if($array_index%2 == 0 && $query_parts[$array_index]!=''){
				$result[$key_value] = $query_parts[$array_index+1];
			}
		}
		return $result;
	}


	//public
	function parseQuery($query){
		//return empty array if query is empty
		if (empty($query)){
			return array();
		}

		parse_str($this->query, $result);
		return $result;
	}


	//public
	//This method will construct a pretty url based on the given query
	function constructPrettyUrl($query){
		global $_config; 
		if (empty($query)){
			return '';
		}

		//do not change query if pretty url is disabled
		if ($_config['pretty_url'] == 0){
			return $query;
		}

		$pretty_url = '';		//init url
		$query_parts = explode(SEP, $query);
		foreach ($query_parts as $index=>$attributes){
			if(empty($attributes)){
				//skip the ones that are empty.
				continue;
			}
			list($key, $value) = preg_split('/\=/', $attributes, 2);
			$pretty_url .= $key . '/' . $value .'/';
		}
		return $pretty_url;
	}


	/**
	 * This function is used to convert the input URL to a pretty URL.
	 * @param	int		course id
	 * @param	string	normal URL, WITHOUT the <prototal>://<host>
	 * @return	pretty url
	 */
	function convertToPrettyUrl($course_id, $url){
		global $_config;
		list($front, $end) = preg_split('/\?/', $url);
		$front_array = explode('/', $front);

		//find out what kind of link this is, pretty url? relative url? or PHP_SELF url?
		$dir_deep	 = substr_count(AT_INCLUDE_PATH, '..');
		$url_parts	 = explode('/', $_SERVER['PHP_SELF']);
		$host_dir	 = implode('/', array_slice($url_parts, 0, count($url_parts) - $dir_deep-1));

		//The link is a bounce link
		if(preg_match('/bounce.php\?course=([\d]+)$/', $url, $matches)==1){
			if (!empty($course_id)) {
				$pretty_url = $course_id;		//course_id should be assigned by vitals depending on the system pref.
			} else {
				$pretty_url = $matches[1];		//happens when course dir name is disabled
			}
		} elseif(in_array(AT_PRETTY_URL_HANDLER, $front_array)===TRUE){
			//The relative link is a pretty URL
			$front_result = array();			
			//spit out the URL in between AT_PRETTY_URL_HANDLER to *.php
			//note, pretty url is defined to be AT_PRETTY_URL_HANDLER/course_slug/type/location/...
			//ie. AT_PRETTY_URL_HANDLER/1/forum/view.php/...
			$needle = array_search(AT_PRETTY_URL_HANDLER, $front_array);
			$front_array = array_slice($front_array, $needle + 2);  //+2 because we want the entries after the course_slug
			/* Overwrite pathinfo
			 * ie. /go.php/1/forum/view.php/fid/1/pid/17/?fid=1&pid=17&page=5
			 * In the above case, cut off the original pathinfo, and replace it with the new querystrings
			 * If querystring is empty, then use the old one, ie. /go.php/1/forum/view.php/fid/1/pid/17/.
			 */
			foreach($front_array as $fk=>$fv){
				array_push($front_result, $fv);
				if 	($end!='' && preg_match('/\.php/', $fv)==1){
					break;
				}
			}
			$front = implode('/', $front_result);
		} elseif (strpos($front, $host_dir)!==FALSE){
			//Not a relative link, it contains the full PHP_SELF path.
			$front = substr($front, strlen($host_dir)+1);  //stripe off the slash after the host_dir as well
		} elseif ($course_id == ''){
			//if this is my start page
			return $url;
		}
		//Turn querystring to pretty URL
		if ($pretty_url==''){
			//Add course id in if it's not there.
			if (preg_match('/'.$course_id.'\//', $front)==0){
				$pretty_url = $course_id.'/';
			}
			$pretty_url .= $front;

			//check if there are any rules overwriting the original rules
			//TODO: have a better way to do this
			//		extend modularity into this.
			$obj =& $this;  //default
			//Overwrite the UrlRewrite obj if there are any private rules
			if ($_config['apache_mod_rewrite'] > 0){
				if (preg_match('/forum\/(index|view|list)\.php/', $front)==1) {
					$pretty_url = $course_id.'/forum';
					$obj =& new ForumsUrl();
				} elseif (preg_match('/content\.php/', $front)==1){
					$pretty_url = $course_id.'/content';
					$obj =& new ContentUrl();
				} elseif (preg_match('/file_storage\/((index|revisions|comments)\.php)?/', $front, $matches)==1){
					$pretty_url = $course_id.'/file_storage';
					$obj =& new FileStorageUrl($matches[1]);
				} 
			}
			if ($end != ''){
				//if pretty url is turned off, use '?' to separate the querystring.
				($_config['pretty_url'] == 0)? $qs_sep = '?': $qs_sep = '/';
				 $pretty_url .= $qs_sep.$obj->constructPrettyUrl($end);
			}
		}

		//if mod_rewrite is switched on, defined in constants.inc.php
		if ($_config['apache_mod_rewrite'] > 0){
			return $pretty_url;
		}

		return AT_PRETTY_URL_HANDLER.'/'.$pretty_url;
	}


	/**
	 * Return the paths where this script is
	 */
	function getPath(){
		if ($this->path != ''){
			return substr($this->path, 1).'/';
		}
		return '';
	}

	/**
	 * Return the script name
	 */
	function getFileName(){
		return $this->filename;
	}

	/**
	 * 
	 */
	function getPage(){
		return $this->getPath().$this->getFileName();
	}

	/**
	 * 
	 */
	function isEmpty(){
		return $this->isEmpty;
	}
}
?>