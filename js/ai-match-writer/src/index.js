import { createRoot } from "@wordpress/element";
import { ConfigProvider } from "antd";
import AIMatchWriter from "./AIMatchWriter";
import "./style.css";

const App = () => {
  return (
    <ConfigProvider
      theme={{
        token: {
          // Seed Token
          // colorPrimary: "#a2cd3a",
          // colorTextBase: "#D9D9D9",
          // colorBgContainer: "black",
          // Alias Token
          // colorBgContainer: "transparent",
          // miniContentHeight: "10px"
        }
      }}
    >
      <AIMatchWriter />
    </ConfigProvider>
  );
};
createRoot(document.getElementById("ai-match-writer")).render(<App />);

// Find all DOM containers, and render order form into them.
// document.querySelectorAll(".react-calendar").forEach((domContainer) => {
//   createRoot(domContainer).render(<App />);
// });
