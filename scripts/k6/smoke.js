import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
  stages: [
    { duration: "10s", target: 5 },
    { duration: "20s", target: 10 },
    { duration: "10s", target: 0 },
  ],
  thresholds: {
    http_req_failed: ["rate<0.05"],
    http_req_duration: ["p(95)<1500"],
  },
};

const BASE_URL = (__ENV.BASE_URL || "http://localhost:8000").replace(/\/$/, "");
const TEST_ENDPOINT = "/api/test-cors"; // public route in backend/routes/api.php

export default function () {
  const res = http.get(`${BASE_URL}${TEST_ENDPOINT}`);
  check(res, {
    "status is 200": (r) => r.status === 200,
    "message is CORS test": (r) => r.json("message") === "CORS test",
  });
  sleep(1);
}
