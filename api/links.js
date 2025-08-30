import { kv } from '@vercel/kv';

export const config = {
  runtime: 'edge',
};

export default async function handler(request) {
  try {
    // Handle GET request to fetch links
    if (request.method === 'GET') {
      const links = await kv.get('links');
      if (!links) {
        // If no links are found, return an empty array
        return new Response(JSON.stringify([]), {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        });
      }
      return new Response(JSON.stringify(links), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Handle POST request to save links
    if (request.method === 'POST') {
      const body = await request.json();
      await kv.set('links', body);
      return new Response(JSON.stringify({ message: 'Links saved successfully' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Handle other methods
    return new Response(JSON.stringify({ message: 'Method Not Allowed' }), {
      status: 405,
      headers: { 'Content-Type': 'application/json' },
    });

  } catch (error) {
    console.error(error);
    return new Response(JSON.stringify({ message: 'Internal Server Error' }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' },
    });
  }
}
