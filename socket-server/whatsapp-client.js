const API_VERSION =
  process.env.WHATSAPP_API_VERSION ||
  process.env.WHATSAPP_GRAPH_VERSION ||
  "v22.0";

const DEFAULT_TIMEOUT_MS = Number(
  process.env.WHATSAPP_TIMEOUT_MS || process.env.DOCTOR_ALERT_TIMEOUT_MS || 5000
);

const ENV_ACCESS_TOKEN =
  process.env.WHATSAPP_ACCESS_TOKEN || process.env.WHATSAPP_BEARER_TOKEN || "";
const ENV_PHONE_NUMBER_ID =
  process.env.WHATSAPP_PHONE_NUMBER_ID ||
  process.env.WHATSAPP_FROM_NUMBER_ID ||
  "";

const buildEndpoint = (phoneNumberId) =>
  `https://graph.facebook.com/${API_VERSION}/${phoneNumberId}/messages`;

const sanitizeNumber = (input) =>
  input?.replace?.(/[^\d+]/g, "")?.replace(/^00/, "+")?.trim() || "";

const resolveConfig = (overrides = {}) => {
  const accessToken = overrides.accessToken || ENV_ACCESS_TOKEN;
  const phoneNumberId = overrides.phoneNumberId || ENV_PHONE_NUMBER_ID;

  if (!accessToken || !phoneNumberId) {
    throw new Error("WhatsApp credentials missing");
  }

  return {
    accessToken,
    phoneNumberId,
    timeoutMs: overrides.timeoutMs || DEFAULT_TIMEOUT_MS,
  };
};

const dispatch = async (payload, overrides = {}) => {
  const { accessToken, phoneNumberId, timeoutMs } = resolveConfig(overrides);
  const to = sanitizeNumber(payload?.to);
  if (!to) {
    throw new Error("WhatsApp recipient number missing");
  }

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetch(buildEndpoint(phoneNumberId), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${accessToken}`,
      },
      signal: controller.signal,
      body: JSON.stringify(payload),
    });

    const body = await response.text();

    if (!response.ok) {
      let errorDetail = "";
      try {
        errorDetail = JSON.parse(body);
      } catch {
        errorDetail = body;
      }

      const error = new Error(
        `WhatsApp API request failed (status ${response.status})`
      );
      error.status = response.status;
      error.details = errorDetail;
      throw error;
    }

    try {
      return JSON.parse(body);
    } catch {
      return body;
    }
  } finally {
    clearTimeout(timer);
  }
};

export const isWhatsAppConfigured = () => {
  try {
    resolveConfig();
    return true;
  } catch {
    return false;
  }
};

export const sendWhatsAppText = async (
  to,
  body,
  options = {}
) => {
  if (!body?.trim()) {
    throw new Error("WhatsApp text body missing");
  }

  return dispatch(
    {
      messaging_product: "whatsapp",
      to,
      type: "text",
      text: {
        preview_url: Boolean(options.previewUrl ?? false),
        body,
      },
    },
    options
  );
};

export const sendWhatsAppTemplate = async (
  to,
  templateName,
  options = {}
) => {
  if (!templateName?.trim()) {
    throw new Error("WhatsApp template name missing");
  }

  const language =
    options.language ||
    process.env.WHATSAPP_TEMPLATE_LANGUAGE ||
    process.env.WHATSAPP_LANGUAGE_CODE ||
    "en_US";

  const payload = {
    messaging_product: "whatsapp",
    to,
    type: "template",
    template: {
      name:
        templateName ||
        process.env.WHATSAPP_TEMPLATE_NAME ||
        process.env.WHATSAPP_TEMPLATE ||
        "hello_world",
      language: {
        code: language,
      },
    },
  };

  if (options.components && options.components.length) {
    payload.template.components = options.components;
  }

  return dispatch(payload, options);
};

export const getWhatsAppConfig = () => {
  if (!isWhatsAppConfigured()) {
    return null;
  }

  return {
    accessToken: ENV_ACCESS_TOKEN,
    phoneNumberId: ENV_PHONE_NUMBER_ID,
    apiVersion: API_VERSION,
    timeoutMs: DEFAULT_TIMEOUT_MS,
  };
};
