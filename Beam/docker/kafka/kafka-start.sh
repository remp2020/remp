#!/bin/bash

echo "Starting kafka"
create-topics.sh &
/start.sh