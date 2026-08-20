type Listener = () => void;

const listeners = new Set<Listener>();
let activeRequestCount = 0;

const publish = (): void => {
  listeners.forEach(listener => listener());
};

export const subscribeToRequestActivity = (listener: Listener): (() => void) => {
  listeners.add(listener);
  return () => listeners.delete(listener);
};

export const getActiveRequestCount = (): number => activeRequestCount;

export const beginRequestActivity = (): (() => void) => {
  activeRequestCount += 1;
  publish();

  let completed = false;
  return () => {
    if (completed) return;
    completed = true;
    activeRequestCount = Math.max(0, activeRequestCount - 1);
    publish();
  };
};

export const trackRequestActivity = async <T>(
  operation: () => Promise<T>
): Promise<T> => {
  const complete = beginRequestActivity();
  try {
    return await operation();
  } finally {
    complete();
  }
};

export const resetRequestActivityForTests = (): void => {
  activeRequestCount = 0;
  publish();
};
