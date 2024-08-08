#!/bin/bash

docker-compose down -v
docker-compose up -d zookeeper zookeeper_2 zookeeper_3 kafka kafka_2 kafka_3
sleep 10
docker-compose up init-kafka
docker-compose rm -f -v init-kafka
docker-compose up -d php nginx
docker-compose up -d consumer-AtualizaDadosPDV consumer-AtualizaDadosPDV_2 consumer-AtualizaDadosPDV_3 \
    consumer-GravaBinarioMovimento consumer-GravaBinarioMovimento_2 consumer-GravaBinarioMovimento_3 \
    consumer-subirXMLNota consumer-subirXMLNota_2 consumer-subirXMLNota_3

