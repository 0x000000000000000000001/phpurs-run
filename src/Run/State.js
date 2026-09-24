// The PHP backend uses State.php; this file only satisfies the compiler,
// which requires a foreign implementation for every foreign import.
export const runStateAtImpl = function () {
  throw new Error("Run.State is implemented for the PHP backend only");
};
