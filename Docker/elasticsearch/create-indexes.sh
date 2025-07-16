#!/bin/bash

# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error.
# -o pipefail: The return value of a pipeline is the status of the last command to exit with a non-zero status.
set -euo pipefail

# --- Configuration ---
ELASTICSEARCH_HOST="http://elasticsearch:9200"
INDICES=("pageviews" "pageviews_time_spent" "pageviews_progress" "events" "commerce" "impressions")

# --- Index Specific Settings ---
declare -A pageviews=(
  [retention]="90d"
  [mapping]=$'
    {
      "subscriber": {
        "type": "boolean"
      },
      "signed_in": {
        "type": "boolean"
      }
    }'
)
declare -A pageviews_time_spent=(
  [retention]="33d"
  [mapping]=$'
    {
      "subscriber": {
        "type": "boolean"
      },
      "signed_in": {
        "type": "boolean"
      }
    }'
)
declare -A pageviews_progress=(
  [retention]="33d"
  [mapping]=$'
    {
      "subscriber": {
        "type": "boolean"
      },
      "signed_in": {
        "type": "boolean"
      }
    }'
)
declare -A events=(
  [retention]="125d"
  [mapping]="{}"
)
declare -A commerce=(
  [retention]="125d"
  [mapping]=$'
    {
      "revenue": {
        "type": "scaled_float",
        "scaling_factor": 100
      }
    }'
)
declare -A impressions=(
  [retention]="33d"
  [mapping]="{}"
)

# --- Functions ---

function put_policy
{
  local index=$1
  # Use a name reference (-n) to create a temporary alias for the current record
  declare -n current="$index"
  local retention_period="${current[retention]}"

  echo "Upserting ILM policy for '${index}'..."

  # Capture response and http_code in one call
  local response
  response=$(curl -s -w "\n%{http_code}" -XPUT -H "Content-Type: application/json" \
    "${ELASTICSEARCH_HOST}/_ilm/policy/${index}_policy" -d \
    '{
       "policy": {
         "phases": {
           "hot": {
             "min_age": "0ms",
             "actions": {
               "rollover": {
                 "max_age": "30d",
                 "max_primary_shard_size": "4gb"
               },
               "set_priority": {
                 "priority": 100
               }
             }
           },
           "warm": {
             "min_age": "1d",
             "actions": {
               "set_priority": {
                 "priority": 50
               }
             }
           },
           "delete": {
             "min_age": "'"${retention_period}"'",
             "actions": {
               "delete": {}
             }
           }
         }
       }
     }')

  local http_code=$(echo "$response" | tail -n1)
  local body=$(echo "$response" | sed '$d')

  # Check for any non-successful (non-2xx) HTTP status codes.
  if ! [[ "$http_code" =~ ^2[0-9]{2}$ ]]; then
    echo "Error: Failed to create policy for '$index'. Received status $http_code. Response:" >&2
    echo "$body" >&2
    exit 1
  fi
}

function put_index_template
{
  local index=$1
  # Use a name reference (-n) to create a temporary alias for the current record
  declare -n current="$1"
  local mapping_properties="${current[mapping]}"

  echo "Upserting index template for '${index}'..."

  local response
  response=$(curl -s -w "\n%{http_code}" -XPUT -H "Content-Type: application/json" \
    "${ELASTICSEARCH_HOST}/_index_template/${index}" -d \
    '{
      "index_patterns": [
        "'"${index}"'-*"
      ],
      "template": {
        "settings": {
          "index.lifecycle.name": "'"${index}"'_policy",
          "index.lifecycle.rollover_alias": "'"${index}"'"
        },
        "mappings": {
          "properties": '"${mapping_properties}"'
        }
      }
    }')

  local http_code=$(echo "$response" | tail -n1)
  local body=$(echo "$response" | sed '$d')

  # Check for any non-successful (non-2xx) HTTP status codes.
  if ! [[ "$http_code" =~ ^2[0-9]{2}$ ]]; then
    echo "Error: Failed to create template for '$index'. Received status $http_code. Response:" >&2
    echo "$body" >&2
    exit 1
  fi
}

function put_initial_index
{
  local index=$1

  echo "Upserting initial write index for alias '${index}'..."

  local response
  response=$(curl -s -w "\n%{http_code}" -XPUT "${ELASTICSEARCH_HOST}/%3C${index}-%7Bnow%2Fd%7D-000001%3E" -H "Content-Type: application/json" -d \
    '{
      "aliases": {
        "'"${index}"'": {
          "is_write_index": true
        }
      }
    }')

  local http_code=$(echo "$response" | tail -n1)
  local body=$(echo "$response" | sed '$d')

  # Check for any non-successful (non-2xx) HTTP status codes.
  if ! [[ "$http_code" =~ ^2[0-9]{2}$ ]]; then
    echo "Error: Failed to create initial index for '$index'. Received status $http_code. Response:" >&2
    echo "$body" >&2
    exit 1
  fi
}


# --- Main Execution Logic ---

# Loop to wait for Elasticsearch to become available.
for i in {30..0}; do
    # Check if Elasticsearch is responsive.
    if curl -s "${ELASTICSEARCH_HOST}" > /dev/null; then
        echo "Elasticsearch is up. Proceeding with setup..."

        # Iterate over each defined index alias.
        for index in "${INDICES[@]}"; do
          echo "--- Processing setup for '${index}' ---"
          http_status=$(curl --silent --output /dev/null --write-out '%{http_code}' -XGET "${ELASTICSEARCH_HOST}/_alias/${index}")

          # Check if the alias does NOT exist (404).
          if [ "$http_status" -eq 404 ]; then
            echo "Alias '${index}' not found. Creating policy, template, and initial index."

            put_policy "$index"
            put_index_template "$index"
            put_initial_index "$index"

          elif [ "$http_status" -eq 200 ]; then
            echo "Alias '${index}' already exists. Skipping setup."
          else
            echo "Warning: Received unexpected HTTP status ${http_status} for alias '${index}'. Check Elasticsearch status."
          fi
        done

        echo "Initial setup check complete."
        exit 0 # Exit successfully after checking all INDICES.
    fi
    echo "Waiting for Elasticsearch to be available... ($i retries left)"
    sleep 2
done

echo "Error: Elasticsearch did not become available after 30 retries."
exit 1
