import { useState } from "react";
import { Card, Button, Space } from "antd";

const GeneratePost = () => {
  const [loading, setLoading] = useState(false);

  const checkGames = async () => {
    setLoading(true);
    const formData = new FormData();
    formData.append("action", "check_games");

    try {
      const res = await fetch(amw_params.ajax_url, {
        method: "POST",
        headers: { "X-WP-Nonce": amw_params.nonce },
        body: formData
      });

      const data = await res.json();
      setLoading(false);
      if (data.status === "success") {
      }
    } catch (err) {
      console.error("Error fetching team", team?.name, err);
    }
  };
  return (
    <Card
      size="small"
      title="Generate Manually"
      style={{ marginBottom: "40px" }}
    >
      <Space>
        <Button onClick={checkGames} loading={loading}>
          Check Fixtures/Results
        </Button>
      </Space>
    </Card>
  );
};

export default GeneratePost;
