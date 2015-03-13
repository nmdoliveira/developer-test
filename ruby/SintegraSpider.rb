# encoding: utf-8

require_relative 'spider.rb'
require 'json'

#
# Subclass of Spider that makes a CNPJ request to the Sintegra ES website and parses the results into a JSON object.
#
class SintegraSpider < Spider

	#
	# Searches for a CNPJ and parses the resulting HTML page.
	# 
	# param:
	# => +cnpj+ String representation of the CNPJ to be searched.
	#
	# return:
	# => JSON object containing the retrieved information.
	#
	def search(cnpj)
		
		path = '/resultado.php'
		param = {
			'num_cnpj' => cnpj,
			'botao' => 'Consultar'
		}

		response = post path, param
		page = response.body
		parse page

	end

	#
	# Parses a HTML page for the information that can be retrieved in the Sintegra ES CNPJ search.
	#
	# param:
	# => +page+ String representation of the HTML page to be parsed.
	#
	# return:
	# => JSON object containing the retrieved information.
	#
	def parse(page)

		# 
		# Page pre-processing
		#

		# Encoding
		page = page.force_encoding('iso-8859-1').encode('utf-8')
		# Replaces HTML entities
		page.gsub! '&ccedil;', 'ç'
		page.gsub! '&atilde;', 'ã'
		# Removes comments
		page.gsub! /<!--(.*?)-->/m, ''

		# Separation of page content and sections
		/<div id=\"conteudo\".*?>(?<content>.*?)<\/div>/m =~ page
		sections = content.scan(/<td class=\"secao\".*?>(?<section_title>.*?)<(?<section>.*?)<\/table>/m)
		#
		# Parsing of each section
		#

		json = {}
		sections.each do |s|
			# Section title
			st = s[0].strip
			# Section content
			sc = s[1]

			# Fix for last section:
			# Last rows are in separate tables, but are part of the same section;
			# This segment adds the remaining of the content to the last section for correct parsing.
			content.slice!(0, content.index(sc) + sc.length - 1)
			if s == sections.last
				sc << content
			end

			# Separates title and value of fields for this section
			json[st] = {}
			sc.scan(/<td.*?class=\"titulo\".*?>(?:&nbsp;|.*?)(?<key>.*?):.*?<\/td>.*?<td.*?class=\"valor\".*?>(?:&nbsp;|.*?)(?<value>.*?)<\/td>/m) do |key, value|
				json[st][key] = value;
			end
		end

	# JSON generation
	JSON.pretty_generate json
	end
end