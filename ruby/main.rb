require_relative 'SintegraSpider'

uri = 'http://www.sintegra.es.gov.br'
spider = SintegraSpider.new(uri)
puts spider.search "31.804.115-0002-43"