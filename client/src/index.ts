import createClient, { type Client, type ClientOptions } from 'openapi-fetch';
import type { paths } from './schema';

export type { components, operations, paths } from './schema';

export interface CortexClientOptions {
  /**
   * The host's API base URL, its `cortex.routes.prefix` included
   * (e.g. `https://app.test/cortex`): the document's paths are relative to it.
   */
  baseUrl: string;
  /** Headers sent with every request, e.g. an `Authorization` header. */
  headers?: ClientOptions['headers'];
  /** A fetch implementation to use instead of `globalThis.fetch`. */
  fetch?: ClientOptions['fetch'];
  /** Whether cookies are sent, e.g. `include` for a cookie-authenticated host. */
  credentials?: ClientOptions['credentials'];
}

export type CortexClient = Client<paths>;

/**
 * A typed openapi-fetch client for the Cortex JSON API:
 * `client.GET('/search', { params: { query: { q: 'triage' } } })`.
 */
export function createCortexClient(options: CortexClientOptions): Client<paths> {
  return createClient<paths>(options);
}
