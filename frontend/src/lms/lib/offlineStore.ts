import type { Role } from "./laravelApi";

export type OfflineScope = { userId: number; role: Role };
export type OfflineOperationType =
  | "attendance.create"
  | "exam_result.create"
  | "payment.create"
  | "worksheet_submission.submit";
export type OfflineOperationStatus = "queued" | "rejected" | "conflict";
export type OfflineOperation = {
  id: string;
  scopeKey: string;
  type: OfflineOperationType;
  data: Record<string, unknown>;
  occurredAt: string;
  baseUpdatedAt?: string;
  createdAt: number;
  retryCount: number;
  status: OfflineOperationStatus;
  errorCode?: string;
};
export type OfflineSnapshot<T> = {
  key: string;
  scopeKey: string;
  savedAt: number;
  data: T;
};

const DATABASE_NAME = "al-imtiaz-offline";
const DATABASE_VERSION = 1;
const SNAPSHOTS_STORE = "snapshots";
const OUTBOX_STORE = "outbox";
const RESULTS_STORE = "sync_results";
const SCOPE_INDEX = "scopeKey";
export const SNAPSHOT_TTL_MS = 7 * 24 * 60 * 60 * 1000;
export const OUTBOX_TTL_MS = 30 * 24 * 60 * 60 * 1000;
export const MAX_OUTBOX_OPERATIONS = 500;

export function offlineScopeKey(scope: OfflineScope): string {
  return `${scope.userId}:${scope.role}`;
}

export function isExpired(
  savedAt: number,
  ttl: number,
  now = Date.now()
): boolean {
  return savedAt + ttl < now;
}

function requestResult<T>(request: IDBRequest<T>): Promise<T> {
  return new Promise((resolve, reject) => {
    request.onsuccess = () => resolve(request.result);
    request.onerror = () =>
      reject(request.error || new Error("فشل تخزين البيانات محلياً"));
  });
}

function transactionResult(transaction: IDBTransaction): Promise<void> {
  return new Promise((resolve, reject) => {
    transaction.oncomplete = () => resolve();
    transaction.onerror = () =>
      reject(transaction.error || new Error("فشل تخزين البيانات محلياً"));
    transaction.onabort = () =>
      reject(transaction.error || new Error("تم إيقاف التخزين المحلي"));
  });
}

function openDatabase(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    if (!window.indexedDB) {
      reject(
        new Error("المتصفح لا يدعم التخزين المحلي المطلوب للعمل دون اتصال.")
      );
      return;
    }
    const request = window.indexedDB.open(DATABASE_NAME, DATABASE_VERSION);
    request.onerror = () =>
      reject(request.error || new Error("تعذر فتح التخزين المحلي"));
    request.onupgradeneeded = () => {
      const database = request.result;
      for (const storeName of [SNAPSHOTS_STORE, OUTBOX_STORE, RESULTS_STORE]) {
        if (!database.objectStoreNames.contains(storeName)) {
          const store = database.createObjectStore(storeName, {
            keyPath: storeName === SNAPSHOTS_STORE ? "key" : "id",
          });
          store.createIndex(SCOPE_INDEX, SCOPE_INDEX, { unique: false });
        }
      }
    };
    request.onsuccess = () => resolve(request.result);
  });
}

async function recordsForScope<T>(
  storeName: string,
  scope: OfflineScope
): Promise<T[]> {
  const database = await openDatabase();
  const transaction = database.transaction(storeName, "readonly");
  const store = transaction.objectStore(storeName);
  const records = await requestResult(
    store.index(SCOPE_INDEX).getAll(offlineScopeKey(scope)) as IDBRequest<T[]>
  );
  await transactionResult(transaction);
  database.close();
  return records;
}

export async function cacheOfflineSnapshot<T>(
  scope: OfflineScope,
  data: T
): Promise<void> {
  const database = await openDatabase();
  const transaction = database.transaction(SNAPSHOTS_STORE, "readwrite");
  const scopeKey = offlineScopeKey(scope);
  transaction.objectStore(SNAPSHOTS_STORE).put({
    key: scopeKey,
    scopeKey,
    savedAt: Date.now(),
    data,
  } satisfies OfflineSnapshot<T>);
  await transactionResult(transaction);
  database.close();
}

export async function readOfflineSnapshot<T>(
  scope: OfflineScope
): Promise<T | null> {
  const database = await openDatabase();
  const transaction = database.transaction(SNAPSHOTS_STORE, "readwrite");
  const scopeKey = offlineScopeKey(scope);
  const snapshot = await requestResult(
    transaction.objectStore(SNAPSHOTS_STORE).get(scopeKey) as IDBRequest<
      OfflineSnapshot<T> | undefined
    >
  );
  if (!snapshot || isExpired(snapshot.savedAt, SNAPSHOT_TTL_MS)) {
    if (snapshot) transaction.objectStore(SNAPSHOTS_STORE).delete(scopeKey);
    await transactionResult(transaction);
    database.close();
    return null;
  }
  await transactionResult(transaction);
  database.close();
  return snapshot.data;
}

export async function enqueueOfflineOperation(
  scope: OfflineScope,
  operation: Omit<
    OfflineOperation,
    "id" | "scopeKey" | "createdAt" | "retryCount" | "status"
  >
): Promise<OfflineOperation> {
  const current = await readOfflineOperations(scope);
  if (current.length >= MAX_OUTBOX_OPERATIONS) {
    throw new Error(
      "وصلت قائمة العمليات المحلية إلى الحد الآمن. اتصل بالإنترنت ثم نفّذ المزامنة."
    );
  }
  const lastCreatedAt = current.reduce(
    (latest, item) => Math.max(latest, item.createdAt),
    0
  );
  const record: OfflineOperation = {
    ...operation,
    id: crypto.randomUUID(),
    scopeKey: offlineScopeKey(scope),
    createdAt: Math.max(Date.now(), lastCreatedAt + 1),
    retryCount: 0,
    status: "queued",
  };
  const database = await openDatabase();
  const transaction = database.transaction(OUTBOX_STORE, "readwrite");
  transaction.objectStore(OUTBOX_STORE).put(record);
  await transactionResult(transaction);
  database.close();
  return record;
}

export async function readOfflineOperations(
  scope: OfflineScope
): Promise<OfflineOperation[]> {
  const records = await recordsForScope<OfflineOperation>(OUTBOX_STORE, scope);
  return records
    .filter(record => !isExpired(record.createdAt, OUTBOX_TTL_MS))
    .sort((left, right) => left.createdAt - right.createdAt);
}

export async function replaceOfflineOperation(
  operation: OfflineOperation
): Promise<void> {
  const database = await openDatabase();
  const transaction = database.transaction(OUTBOX_STORE, "readwrite");
  transaction.objectStore(OUTBOX_STORE).put(operation);
  await transactionResult(transaction);
  database.close();
}

export async function removeOfflineOperation(id: string): Promise<void> {
  const database = await openDatabase();
  const transaction = database.transaction(OUTBOX_STORE, "readwrite");
  transaction.objectStore(OUTBOX_STORE).delete(id);
  await transactionResult(transaction);
  database.close();
}

export async function clearOfflineScope(scope: OfflineScope): Promise<void> {
  const database = await openDatabase();
  const scopeKey = offlineScopeKey(scope);
  const transaction = database.transaction(
    [SNAPSHOTS_STORE, OUTBOX_STORE, RESULTS_STORE],
    "readwrite"
  );
  for (const storeName of [SNAPSHOTS_STORE, OUTBOX_STORE, RESULTS_STORE]) {
    const store = transaction.objectStore(storeName);
    const rows = await requestResult(
      store.index(SCOPE_INDEX).getAllKeys(scopeKey)
    );
    rows.forEach(key => store.delete(key));
  }
  await transactionResult(transaction);
  database.close();
}
