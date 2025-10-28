#!/bin/bash

echo "🔍 Test CORS - Backend API"
echo "================================"
echo ""

echo "✅ Test 1: Route publique (entry-diplomas)"
curl -s -w "\nStatus: %{http_code}\n" \
  http://127.0.0.1:8000/api/public/entry-diplomas \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  | grep -E '(Access-Control-Allow-Origin|Status:|success)'

echo ""
echo "✅ Test 2: Route publique (academic-years)"
curl -s -w "\nStatus: %{http_code}\n" \
  http://127.0.0.1:8000/api/public/academic-years \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  | grep -E '(Access-Control-Allow-Origin|Status:|success)'

echo ""
echo "✅ Test 3: Route POST dossiers (validation attendue: 422)"
curl -s -w "\nStatus: %{http_code}\n" \
  -X POST http://127.0.0.1:8000/api/dossiers/licence \
  -H "Origin: http://localhost:5173" \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  | grep -E '(Access-Control-Allow-Origin|Status:|message)'

echo ""
echo "✅ Test 4: Preflight OPTIONS"
curl -s -w "\nStatus: %{http_code}\n" \
  -X OPTIONS http://127.0.0.1:8000/api/dossiers/licence \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  | grep -E '(Access-Control-Allow|Status:)'

echo ""
echo "================================"
echo "✅ Si tous les tests montrent 'Access-Control-Allow-Origin', CORS est OK!"
