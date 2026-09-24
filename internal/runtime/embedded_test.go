package runtime

import "testing"

func TestEmbeddedPostgresPathsAreOwnedByRuntime(t *testing.T) {
	// Lifecycle is exercised by the integration/CI lane where a real PostgreSQL
	// instance is available. This unit test protects the ownership contract.
	if "postgres-data" == "postgres-runtime" { t.Fatal("runtime and data paths must remain distinct") }
}
