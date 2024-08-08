# Kafka Manager

## Instructions

Up containers:

```
docker-compose up -d zookeeper kafka
docker-compose up init-kafka
docker-compose rm -f -v init-kafka
docker-compose up -d php nginx
docker-compose up -d consumer-AtualizaDadosPDV consumer-AtualizaDadosPDV_2 consumer-AtualizaDadosPDV_3 \
    consumer-GravaBinarioMovimento consumer-GravaBinarioMovimento_2 consumer-GravaBinarioMovimento_3 \
    consumer-subirXMLNota consumer-subirXMLNota_2 consumer-subirXMLNota_3
```

## Interacting with Kafka

Enter container:

```
docker exec -it kafka_kafka_1 bash
```

List groups:

```
kafka-consumer-groups --bootstrap-server localhost:19092 --group a --describe
```


List topics:

```
kafka-topics --bootstrap-server localhost:19092 --describe
```

Create topic:

```
kafka-topics --bootstrap-server localhost:19092 --create --topic topic1
```

Activate consumer:

```
kafka-console-consumer --bootstrap-server localhost:19092 --topic topic1
```

Activate producer:

```
kafka-console-producer --bootstrap-server localhost:19092 --topic topic1
```
