# Kafka Manager

## O que é o Kafka?

O Apache Kafka é um sistema de processamento de mensagens de forma assíncrona.

Ele foi feito para ser capaz de suportar uma alta demanda de requisições com
uma latência mínima.

## Instalação

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

## Definições

- Kafka: container responsável pelo tratamento e organização das mensagens
- Zookeeper: container responsável pela organização dos nodes Kafka
- Réplicas: redundância das mensagens de acordo com a quantidade de containers kafka
- Tópicos: maneira usada pelo Kafka para organizar as mensagens
- Partições: separação de mensagens dentro de cada tópico (facilita o processo assíncrono)
- Producer: responsável por produzir a mensagem para um tópico
- Consumer: responsável por processar a mensagem de um tópico

## Perguntas

### Qual é o fluxo?

O PDV envia as requisições para o Manager. Caso o Kafka esteja ativo (ZMWSInfo.ini),
a requisição será enviada via chamada HTTP do Manager para o Producer do Kafka.

Após a produção da mensagem em um determinado tópico, o consumer do tópico correspondente
receberá a mensagem e enviará de volta ao manager via SOAP.

Depois do envio da mensagem pelo consumer, a requisição será processada normalmente pelo Manager.

Caso haja algum problema neste processo dentro do consumer, a mensagem em questão será excluída
do tópico atual e enviada para o tópico de erros (KafkaErrors), onde o consumer de erros irá cadastrá-la
no banco de dados do Manager via SOAP.

Esse erro poderá ser verificado, reprocessado ou excluído manualmente no painel de Gerenciamento do Kafka
dentro do Manager.

Existe também um serviço automático do Mirage capaz de consultar os erros do Kafka e reprocessá-los
automaticamente. Esse processo pode acontecer até 5 vezes. Após 5 falhas no reprocessamento automático,
o Mirage irá ignorar tal erro, podendo assim ser reprocessado somente via o painel de de gerenciamento.

### Como implementar mais funções do PDV?

Para implementar mais funções do PDV para funcionar com o Kafka siga o passo a passo:

- Preparar o Manager
    - Adicione o parâmetro Kafka na rota da função do SOAP
    - Adicione a chamada da função do Kafka na função escolhida
- Crie o tópico no `docker-compose.yml` no container `init-kafka`
- Crie um ou mais consumers para processar as mensagens do tópico
- Crie os containers dos consumers em `docker-compose.yml`
- Adicione os containers do consumer no arquivo `restart_kafka.sh`
- Reinicie os containers

### Como adicionar mais consumers para um mesmo tópico?

Para adicionar mais consumers ao mesmo tópico, por conta de alta demanda ou instabilidade,
siga os passos abaixo:

- Crie um arquivo `public/consumerNovoTópico_4.php` e preencha o tópico e a partição
    - **Importante**: não pode haver dois consumers do mesmo tópico com a mesma partição
- Crie o container do consumer em `docker-compose.yml`
- Adicione o container do consumer no arquivo `restart_kafka.sh`
- Reinicie os containers

### Como configurar o MSK?

Para configurar o MSK basta alterar o `broker_list` em `kafka.ini`.

Use as referências 3 e 4.

## Notas

- Qualquer alteração no `php.ini.example` deve ser seguida de um build completo da imagem php

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


Listar tópics:

```
kafka-topics --bootstrap-server localhost:19092 --describe
```

Criar tópico:

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

## Referências:

1. https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/book.rdkafka.html
2. https://medium.com/@Omid_dev/apache-kafka-a-comprehensive-guide-with-php-examples-4cf6a6491666
3. https://docs.aws.amazon.com/pt_br/msk/latest/developerguide/getting-started.html
4. https://docs.aws.amazon.com/pt_br/systems-manager/latest/userguide/session-manager-working-with-sessions-start.html
5. https://github.com/arnaud-lb/php-rdkafka
6. https://github.com/php-kafka/php-kafka-examples
