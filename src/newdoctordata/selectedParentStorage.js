const SELECTED_PARENT_STORAGE_KEY = "newDoctor.selectedParent";
const EXISTING_PARENT_FLOW_QUERY_KEY = "existingParentFlow";

const hasWindow = () => typeof window !== "undefined";

export const buildExistingParentFlowSearch = () =>
  `?${EXISTING_PARENT_FLOW_QUERY_KEY}=1`;

export const isExistingParentFlowSearch = (search = "") =>
  new URLSearchParams(search).get(EXISTING_PARENT_FLOW_QUERY_KEY) === "1";

export const writeStoredDoctorSelectedParent = (parent) => {
  if (!hasWindow()) return;

  try {
    if (!parent || typeof parent !== "object") {
      window.sessionStorage.removeItem(SELECTED_PARENT_STORAGE_KEY);
      return;
    }

    window.sessionStorage.setItem(
      SELECTED_PARENT_STORAGE_KEY,
      JSON.stringify(parent),
    );
  } catch {
    // Ignore session storage failures and keep navigation state as fallback.
  }
};

export const readStoredDoctorSelectedParent = () => {
  if (!hasWindow()) return null;

  try {
    const rawValue = window.sessionStorage.getItem(SELECTED_PARENT_STORAGE_KEY);
    if (!rawValue) return null;

    const parsedValue = JSON.parse(rawValue);
    return parsedValue && typeof parsedValue === "object" ? parsedValue : null;
  } catch {
    return null;
  }
};

export const clearStoredDoctorSelectedParent = () => {
  if (!hasWindow()) return;

  try {
    window.sessionStorage.removeItem(SELECTED_PARENT_STORAGE_KEY);
  } catch {
    // Ignore session storage failures.
  }
};
