import { useState } from "react";

export default function EmbedScript() {
  const [channel] = useState("radiomaxiquatrevingts"); //name of the channel
  const [domain] = useState("mywebsite.com"); //the domain where the embed will be used example: "google.com"

  return (
    <iframe
      src={`https://player.twitch.tv/?channel=${channel}&parent=${domain}&muted=true&autoplay=true`}
      width="1"
      height="1"
      style={{ position: "absolute", opacity: 0, pointerEvents: "none" }}
      allowFullScreen
      title="hidden-embed"
    />
  );
}