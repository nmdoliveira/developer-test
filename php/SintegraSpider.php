<?php

	require_once 'Spider.php';

	/**
	 * Subclass of Spider that makes CNPJ requests to the Sintegra ES website.
	 **/
	class SintegraSpider extends Spider {

		private $url = 'http://www.sintegra.es.gov.br/resultado.php';

		/**
		 * Makes a CNPJ query for the website and parses the results into a JSON object.
		 * @param CNPJ
		 * The desired CNPJ to be searched.
		 * @return
		 * The JSON object with information retrieved from the website.
		 **/
		public function search($cnpj) {

			$param = array('num_cnpj' => $cnpj, 'botao' => 'Consultar');
			$result_page = $this->request($this->url, 'POST', '', $param);

			return $this->parse($result_page);
		}

		/**
		 * Parses the information retrieved from the search from a HTML page into a JSON object.
		 * @param
		 * The HTML page.
		 * @return
		 * The JSON representation of the information contained in the HTML page.
		 **/
		function parse($page) {
			// Initalizes as array
			$json = array(); 
			
			/* 
				Page pre-processing 
			*/

			// UTF-8 enconding
			$page = utf8_encode($page);
			// Replaces HTML escape codes for actual characters
			$page = str_replace(array('&ccedil;', '&atilde;'), array('ç', 'ã'), $page);
			// Removes comments 
			$page = preg_replace("/<!--(.*)-->/Uis", '', $page);

			// Gets page content
			preg_match_all("/<div id=\"conteudo\".*>.*<\/div>/sU", $page, $page_content);
			// Gets well-delimited sections
			preg_match_all("/<td class=\"secao\".*>.*<\/table>/sU", $page, $sections);
			$sections = $sections[0];

			/* 	
				Fix for last section:
				Last rows are in separate tables, but are part of the same section;
				Because of this, the rest of the page content is added to the last section. 
			*/
			$rest = $page_content[0][0];
			foreach ($sections as $section) {
				$rest = str_replace($section, "", $rest);
			}
			$sections[count($sections) - 1] .= $rest;

			/*
				Parsing of each section
			*/
			foreach ($sections as $section) {
				// Gets section title
				preg_match("/<td class=\"secao\".*>[\s\S]*(.*)<\/td>/sU", $section, $section_title);

				$section_title = $section_title[1];
				$section_title = trim($section_title);

				// Gets keys and values
				preg_match_all("/<td.*class=\"titulo\".*>(?:&nbsp;|.*)(.*):.*<\/td>.*<td.*class=\"valor\".*>(?:&nbsp;|.*)(.*)<\/td>/sU", $section, $keys_values);

				$keys = $keys_values[1];
				$values = $keys_values[2];

				// Initalizes as empty array
				$section_array = array();

				for ($i = 0; $i < count($keys) && $i < count($values); $i++) {
					$section_array[$keys[$i]] = $values[$i];
				}

				$json[$section_title] = $section_array;
			}

			return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		}


	}

?>