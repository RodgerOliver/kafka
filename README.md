# Kafka Manager

## O que � o Kafka?

O Apache Kafka � um sistema de processamento de mensagens de forma ass�ncrona.

Ele foi feito para ser capaz de suportar uma alta demanda de requisi��es com
uma lat�ncia m�nima.

## Instala��o

1. Criar kafka.ini

```
cp kafka.ini.example kafka.ini
```

2. Criar imagens e subir containers:

```
docker-compose up -d --build
```

3. Reiniciar containers:

```
./restart_kafka.sh
```

4. Configurar ZMWSInfo.ini

```
URL_KAFKA=http://192.168.0.123
URL_MANAGER=http://192.168.0.123
PORTA=81
TRATA_FILA_REQ=S
GravaBinarioMovimento=/manager/manager_webservices.php5?wsdl
AtualizaDadosPDV=/manager/manager_webservices.php5?wsdl
subirXMLNota=/manager/web_services/ws_nf_pdv/server.php5?wsdl
EndpointError=/manager/manager_webservices.php5?wsdl
```

## Defini��es

- Kafka: container respons�vel pelo tratamento e organiza��o das mensagens
- Zookeeper: container respons�vel pela organiza��o dos nodes Kafka
- R�plicas: redund�ncia das mensagens de acordo com a quantidade de containers kafka
- T�picos: maneira usada pelo Kafka para organizar as mensagens
- Parti��es: separa��o de mensagens dentro de cada t�pico (facilita o processo ass�ncrono)
- Producer: respons�vel por produzir a mensagem para um t�pico
- Consumer: respons�vel por processar a mensagem de um t�pico

## Perguntas

### Qual � o fluxo?

O PDV envia as requisi��es para o Manager. Caso o Kafka esteja ativo (ZMWSInfo.ini),
a requisi��o ser� enviada via chamada HTTP do Manager para o Producer do Kafka.

Ap�s a produ��o da mensagem em um determinado t�pico, o consumer do t�pico correspondente
receber� a mensagem e enviar� de volta ao manager via SOAP.

Depois do envio da mensagem pelo consumer, a requisi��o ser� processada normalmente pelo Manager.

Caso haja algum problema neste processo dentro do consumer, a mensagem em quest�o ser� exclu�da
do t�pico atual e enviada para o t�pico de erros (KafkaErrors), onde o consumer de erros ir� cadastr�-la
no banco de dados do Manager via SOAP.

Esse erro poder� ser verificado, reprocessado ou exclu�do manualmente no painel de Gerenciamento do Kafka
dentro do Manager.

Existe tamb�m um servi�o autom�tico do Mirage capaz de consultar os erros do Kafka e reprocess�-los
automaticamente. Esse processo pode acontecer at� 5 vezes. Ap�s 5 falhas no reprocessamento autom�tico,
o Mirage ir� ignorar tal erro, podendo assim ser reprocessado somente via o painel de de gerenciamento.

### Como implementar mais fun��es do PDV?

Para implementar mais fun��es do PDV para funcionar com o Kafka siga o passo a passo:

- Preparar o Manager
    - Adicione o par�metro Kafka na rota da fun��o do SOAP
    - Adicione a chamada da fun��o do Kafka na fun��o escolhida
- Crie o t�pico no `docker-compose.yml` no container `init-kafka`
- Crie um ou mais consumers para processar as mensagens do t�pico
- Crie os containers dos consumers em `docker-compose.yml`
- Adicione os containers do consumer no arquivo `restart_kafka.sh`
- Reinicie os containers

### Como adicionar mais consumers para um mesmo t�pico?

Para adicionar mais consumers ao mesmo t�pico, por conta de alta demanda ou instabilidade,
siga os passos abaixo:

- Crie um arquivo `public/consumerNovoT�pico_4.php` e preencha o t�pico e a parti��o
    - **Importante**: n�o pode haver dois consumers do mesmo t�pico com a mesma parti��o
- Crie o container do consumer em `docker-compose.yml`
- Adicione o container do consumer no arquivo `restart_kafka.sh`
- Reinicie os containers

### Como configurar o MSK?

Para configurar o MSK basta alterar o `broker_list` em `kafka.ini`.

Use as refer�ncias 3 e 4.

## Notas

- Qualquer altera��o no `php.ini.example` deve ser seguida de um build completo da imagem php

```
docker-compose build --no-cache php
./restart_kafka.sh
```

## Interagindo com o Kafka

Entrar no container:

```
docker exec -it kafka_kafka_1 bash
```

Listar grupos:

```
kafka-consumer-groups --bootstrap-server localhost:19092 --group a --describe
```


Listar t�pics:

```
kafka-topics --bootstrap-server localhost:19092 --describe
```

Criar t�pico:

```
kafka-topics --bootstrap-server localhost:19092 --create --topic topic1
```

Ativar consumer:

```
kafka-console-consumer --bootstrap-server localhost:19092 --topic topic1
```

Ativar producer:

```
kafka-console-producer --bootstrap-server localhost:19092 --topic topic1
```

## Refer�ncias:

1. https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/book.rdkafka.html
2. https://medium.com/@Omid_dev/apache-kafka-a-comprehensive-guide-with-php-examples-4cf6a6491666
3. https://docs.aws.amazon.com/pt_br/msk/latest/developerguide/getting-started.html
4. https://docs.aws.amazon.com/pt_br/systems-manager/latest/userguide/session-manager-working-with-sessions-start.html
5. https://github.com/arnaud-lb/php-rdkafka
6. https://github.com/php-kafka/php-kafka-examples
