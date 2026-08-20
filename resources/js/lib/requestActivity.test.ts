import { afterEach, describe, expect, it } from "vitest";
import {
  beginRequestActivity,
  getActiveRequestCount,
  resetRequestActivityForTests,
  subscribeToRequestActivity,
  trackRequestActivity,
} from "./requestActivity";

describe("request activity", () => {
  afterEach(() => resetRequestActivityForTests());

  it("tracks overlapping requests and completes each one exactly once", () => {
    const observed: number[] = [];
    const unsubscribe = subscribeToRequestActivity(() => {
      observed.push(getActiveRequestCount());
    });
    const completeFirst = beginRequestActivity();
    const completeSecond = beginRequestActivity();

    expect(getActiveRequestCount()).toBe(2);
    completeFirst();
    completeFirst();
    completeSecond();

    expect(getActiveRequestCount()).toBe(0);
    expect(observed).toEqual([1, 2, 1, 0]);
    unsubscribe();
  });

  it("clears activity when a tracked request rejects", async () => {
    await expect(
      trackRequestActivity(async () => {
        throw new Error("network failed");
      })
    ).rejects.toThrow("network failed");

    expect(getActiveRequestCount()).toBe(0);
  });
});
