const ASK_BOOKING_ATTACHMENT_DB = "snoutiq-ask-booking-db";
const ASK_BOOKING_ATTACHMENT_STORE = "attachments";
const ASK_BOOKING_ATTACHMENT_KEY = "latest-ask-attachment";
const ASK_BOOKING_ATTACHMENT_DB_VERSION = 1;

const openAttachmentDb = () =>
  new Promise((resolve, reject) => {
    if (typeof window === "undefined" || !window.indexedDB) {
      resolve(null);
      return;
    }

    const request = window.indexedDB.open(
      ASK_BOOKING_ATTACHMENT_DB,
      ASK_BOOKING_ATTACHMENT_DB_VERSION
    );

    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(ASK_BOOKING_ATTACHMENT_STORE)) {
        db.createObjectStore(ASK_BOOKING_ATTACHMENT_STORE);
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () =>
      reject(request.error || new Error("Could not open attachment storage."));
  });

const sanitizeAskBookingAttachment = (value) => {
  const raw = value && typeof value === "object" ? value : {};
  const file = raw.file instanceof Blob ? raw.file : null;
  if (!file) return null;

  const name = String(raw.name || file.name || "ask-attachment").trim();
  const mime = String(raw.mime || raw.type || file.type || "application/octet-stream").trim();
  const sizeValue = Number(raw.size ?? file.size);

  return {
    file,
    name: name || "ask-attachment",
    mime: mime || "application/octet-stream",
    size: Number.isFinite(sizeValue) && sizeValue >= 0 ? sizeValue : 0,
  };
};

const readAttachmentRecord = async () => {
  const db = await openAttachmentDb();
  if (!db) return null;

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(ASK_BOOKING_ATTACHMENT_STORE, "readonly");
    const request = transaction
      .objectStore(ASK_BOOKING_ATTACHMENT_STORE)
      .get(ASK_BOOKING_ATTACHMENT_KEY);

    request.onsuccess = () => resolve(request.result ?? null);
    request.onerror = () =>
      reject(request.error || new Error("Could not read attachment storage."));

    transaction.oncomplete = () => db.close();
    transaction.onabort = () => {
      db.close();
      reject(transaction.error || new Error("Could not finish attachment read."));
    };
  });
};

const writeAttachmentRecord = async (value) => {
  const db = await openAttachmentDb();
  if (!db) return;

  await new Promise((resolve, reject) => {
    const transaction = db.transaction(ASK_BOOKING_ATTACHMENT_STORE, "readwrite");
    const request = transaction
      .objectStore(ASK_BOOKING_ATTACHMENT_STORE)
      .put(value, ASK_BOOKING_ATTACHMENT_KEY);

    request.onsuccess = () => resolve();
    request.onerror = () =>
      reject(request.error || new Error("Could not save attachment storage."));

    transaction.oncomplete = () => db.close();
    transaction.onabort = () => {
      db.close();
      reject(transaction.error || new Error("Could not finish attachment save."));
    };
  });
};

const removeAttachmentRecord = async () => {
  const db = await openAttachmentDb();
  if (!db) return;

  await new Promise((resolve, reject) => {
    const transaction = db.transaction(ASK_BOOKING_ATTACHMENT_STORE, "readwrite");
    const request = transaction
      .objectStore(ASK_BOOKING_ATTACHMENT_STORE)
      .delete(ASK_BOOKING_ATTACHMENT_KEY);

    request.onsuccess = () => resolve();
    request.onerror = () =>
      reject(request.error || new Error("Could not clear attachment storage."));

    transaction.oncomplete = () => db.close();
    transaction.onabort = () => {
      db.close();
      reject(transaction.error || new Error("Could not finish attachment clear."));
    };
  });
};

export const getAskBookingAttachment = async () => {
  try {
    return sanitizeAskBookingAttachment(await readAttachmentRecord());
  } catch {
    return null;
  }
};

export const saveAskBookingAttachment = async (value) => {
  const nextAttachment = sanitizeAskBookingAttachment(value);
  if (!nextAttachment) return;

  try {
    await writeAttachmentRecord(nextAttachment);
  } catch {
    // Ignore storage failures.
  }
};

export const clearAskBookingAttachment = async () => {
  try {
    await removeAttachmentRecord();
  } catch {
    // Ignore storage failures.
  }
};
