<?php
/*
Wikidata ORM
Copyright (C) 2016 Vincent Cloutier

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class Wikidata {

	private $data;

	public function __construct($constructData) {
		$this->data = json_decode($constructData, true);
	}

	public function name($lang = "en") {
		if (isset($this->data['labels'][$lang])) {
			return $this->data['labels'][$lang]['value'];
		} else {
			return "";
		}
	}
	
	public function description ($lang = "en") {
		if (isset($this->data['descriptions'][$lang])) {
			return $this->data['descriptions'][$lang]['value'];
		} else {
			return "";
		}
	}
	
	public function alsoKnownAs($lang = "en") {
		$tmp = Array ();
		if (!isset($this->data['aliases'][$lang])) {
			return $tmp;
		}
		foreach ($this->data['aliases'][$lang] as $allias) {
			array_push($tmp, $allias['value']);
		}
		return $tmp;
	}

	public function isInstanceOf($instanceIDs) {
		if (!isset( $this->data["claims"]["P31"])) {
			return false;
		}
		
		if ( !is_array($instanceIDs) ) {
			$instanceIDs = Array ($instanceIDs);
		}
	
		foreach ($this->data["claims"]["P31"] as $claim) {
			foreach ($instanceIDs as $id) {
				if ($claim['mainsnak']['datavalue']['value']['numeric-id'] == $id) {
					return true;
				}
			} 
		}

		return false;
	}
}
