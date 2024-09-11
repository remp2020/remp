module beam

go 1.22

toolchain go1.22.2

require (
	cloud.google.com/go/pubsub v1.43.0
	github.com/Shopify/sarama v1.23.0
	github.com/avct/uasurfer v0.0.0-20180817072212-dc0ec4fd1e87
	github.com/go-sql-driver/mysql v1.5.0
	github.com/gofrs/uuid v3.3.0+incompatible
	github.com/google/uuid v1.6.0
	github.com/imdario/mergo v0.3.11
	github.com/influxdata/influxdb v1.11.5
	github.com/jmoiron/sqlx v0.0.0-20170430194603-d9bd385d68c0
	github.com/joho/godotenv v1.3.0
	github.com/kelseyhightower/envconfig v1.4.0
	github.com/olivere/elastic/v7 v7.0.14
	github.com/patrickmn/go-cache v2.1.0+incompatible
	github.com/pkg/errors v0.9.1
	goa.design/goa/v3 v3.16.1
	goa.design/plugins/v3 v3.16.1
)

require (
	cloud.google.com/go v0.115.1 // indirect
	cloud.google.com/go/auth v0.9.3 // indirect
	cloud.google.com/go/auth/oauth2adapt v0.2.4 // indirect
	cloud.google.com/go/compute v1.28.0 // indirect
	cloud.google.com/go/compute/metadata v0.5.0 // indirect
	cloud.google.com/go/iam v1.2.0 // indirect
	github.com/AnatolyRugalev/goregen v0.1.0 // indirect
	github.com/DataDog/zstd v1.4.0 // indirect
	github.com/davecgh/go-spew v1.1.1 // indirect
	github.com/dimfeld/httppath v0.0.0-20170720192232-ee938bf73598 // indirect
	github.com/eapache/go-resiliency v1.1.0 // indirect
	github.com/eapache/go-xerial-snappy v0.0.0-20180814174437-776d5712da21 // indirect
	github.com/eapache/queue v1.1.0 // indirect
	github.com/felixge/httpsnoop v1.0.4 // indirect
	github.com/go-bindata/go-bindata v3.1.2+incompatible // indirect
	github.com/go-chi/chi/v5 v5.0.12 // indirect
	github.com/go-logr/logr v1.4.2 // indirect
	github.com/go-logr/stdr v1.2.2 // indirect
	github.com/golang/groupcache v0.0.0-20210331224755-41bb18bfe9da // indirect
	github.com/golang/protobuf v1.5.4 // indirect
	github.com/golang/snappy v0.0.4 // indirect
	github.com/google/s2a-go v0.1.8 // indirect
	github.com/googleapis/enterprise-certificate-proxy v0.3.3 // indirect
	github.com/googleapis/gax-go/v2 v2.13.0 // indirect
	github.com/gorilla/websocket v1.5.1 // indirect
	github.com/hashicorp/go-uuid v1.0.1 // indirect
	github.com/jcmturner/gofork v1.0.0 // indirect
	github.com/josharian/intern v1.0.0 // indirect
	github.com/kr/text v0.2.0 // indirect
	github.com/lib/pq v1.3.0 // indirect
	github.com/mailru/easyjson v0.7.7 // indirect
	github.com/manveru/faker v0.0.0-20171103152722-9fbc68a78c4d // indirect
	github.com/mattn/go-sqlite3 v2.0.3+incompatible // indirect
	github.com/niemeyer/pretty v0.0.0-20200227124842-a10e7caefd8e // indirect
	github.com/pierrec/lz4 v2.0.5+incompatible // indirect
	github.com/rcrowley/go-metrics v0.0.0-20181016184325-3113b8401b8a // indirect
	github.com/sergi/go-diff v1.3.1 // indirect
	go.opencensus.io v0.24.0 // indirect
	go.opentelemetry.io/contrib/instrumentation/google.golang.org/grpc/otelgrpc v0.54.0 // indirect
	go.opentelemetry.io/contrib/instrumentation/net/http/otelhttp v0.54.0 // indirect
	go.opentelemetry.io/otel v1.29.0 // indirect
	go.opentelemetry.io/otel/metric v1.29.0 // indirect
	go.opentelemetry.io/otel/trace v1.29.0 // indirect
	golang.org/x/crypto v0.26.0 // indirect
	golang.org/x/mod v0.17.0 // indirect
	golang.org/x/net v0.28.0 // indirect
	golang.org/x/oauth2 v0.23.0 // indirect
	golang.org/x/sync v0.8.0 // indirect
	golang.org/x/sys v0.24.0 // indirect
	golang.org/x/text v0.17.0 // indirect
	golang.org/x/time v0.6.0 // indirect
	golang.org/x/tools v0.21.1-0.20240508182429-e35e4ccd0d2d // indirect
	google.golang.org/api v0.196.0 // indirect
	google.golang.org/appengine v1.6.8 // indirect
	google.golang.org/genproto v0.0.0-20240903143218-8af14fe29dc1 // indirect
	google.golang.org/genproto/googleapis/api v0.0.0-20240903143218-8af14fe29dc1 // indirect
	google.golang.org/genproto/googleapis/rpc v0.0.0-20240903143218-8af14fe29dc1 // indirect
	google.golang.org/grpc v1.66.0 // indirect
	google.golang.org/protobuf v1.34.2 // indirect
	gopkg.in/check.v1 v1.0.0-20200227125254-8fa46927fb4f // indirect
	gopkg.in/jcmturner/aescts.v1 v1.0.1 // indirect
	gopkg.in/jcmturner/dnsutils.v1 v1.0.1 // indirect
	gopkg.in/jcmturner/goidentity.v3 v3.0.0 // indirect
	gopkg.in/jcmturner/gokrb5.v7 v7.3.0 // indirect
	gopkg.in/jcmturner/rpc.v1 v1.1.0 // indirect
	gopkg.in/yaml.v3 v3.0.1 // indirect
)

replace github.com/olivere/elastic/v7 => github.com/rootpd/elastic/v7 v7.0.0-20240911082621-bad91d4bff6b
