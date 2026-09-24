// The PHP backend uses Reader.php; this file only satisfies the compiler,
// which requires a foreign implementation for every foreign import.
export const runReaderAtImpl = function () {
  throw new Error("Run.Reader is implemented for the PHP backend only");
};
