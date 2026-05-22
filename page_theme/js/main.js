document.addEventListener("alpine:init", () => {
  // Declare Alpine directives
  Alpine.directive("fade-img", (el) => {
    el.style.opacity = 0;
    el.style.transition = "opacity 0.7s ease-in-out";
    el.addEventListener("load", () => {
      el.style.opacity = 1;
    });
  });

  // Setup Pinecone Router
  window.PineconeRouter.settings({
    basePath: "/",
    targetID: "app",
  });

  // Global store
  Alpine.store("core", {
    currentPage: "home",
    showBottomMenu: true,
    sessionToken: null,
    settings: {},
    user: {},

    books: [],
    facets: {},
    favorites: [],
    bookMap: null,
    
  });

  NProgress.configure({
    showSpinner: false,
  });
  document.addEventListener("pinecone-start", () => {
    NProgress.start();
  });
  document.addEventListener("pinecone-end", () => {
    NProgress.done();
  });
  document.addEventListener("fetch-error", (err) =>
    console.error("Error fetching data euy:", err),
  );

  Alpine.data("router", () => ({
    init() {
      
    },
    isLoggedIn(context, controller) {

    },
  }));
});

// Register Service Worker
// and Web Push Notification
const publicKey = "BDSkwRKMHK7WT6hTXe7oj0OJ6q9pqIX61tjZc4jR9b7ldszNsmRb1AbAVVFPxUerbhsOaV9Xa-99IEgUHzr2IcM";
let swRegistration = null;

function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, "+")
    .replace(/_/g, "/");
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}