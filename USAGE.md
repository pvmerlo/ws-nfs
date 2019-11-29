# Usando a biblioteca

## Funções

### Publicas

**__construct($version = '1.0', $charset = 'utf-8')**

**setUrlWsdl( $url )**

**LoteRps( stdClass $std )**

**Rps( stdClass $std )**

**IdentificacaoPrestador( stdClass $std )**

**ListaRps()**

**IdentificacaoRps( stdClass $std )**

**DadosPrestador( stdClass $std )**

**EnderecoPrestador( stdClass $std )**

**EnderecoTomador( stdClass $std )**

**ContatoPrestador( stdClass $std )**

**ContatoTomador( stdClass $std )**

**DadosTomador( stdClass $std )**

**IdentificacaoTomador( stdClass $std )**

**Servico( stdClass $std )**

**Valores( stdClass $std )**

**montar()**

**getXML()**

**consultarUltimoRps( $identificacaoPrestador )**

**consultarUltimoLote( $identificacaoPrestador )**

**finalizarSessao( $hash )**

**cancelarNFse( $identificacaoPrestador, $senha, $nfse, $motivoCancelamento )**

**consultarNFse( $cnpj, $numeroProtocolo )**

**autenticarContribuinte( $cnpj, $senha )**

**enviarNFse( $cnpj, $auth_token )**

**transmitirNFse($cnpj, $senha)**


### Privadas

**makeID()**

**addChild( DOMElement &$parent, $name, $content, $required=TRUE )**
