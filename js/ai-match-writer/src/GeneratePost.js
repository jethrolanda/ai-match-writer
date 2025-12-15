import { useState } from "react";
import { Card, Button, Space } from "antd";

const GeneratePost = () => {
  const [loading, setLoading] = useState(false);
  return (
    <Card size="small" title="Generate" style={{ marginBottom: "40px" }}>
      <Space>
        <Button onClick={() => {}} loading={loading}>
          Check Fixtures/Results
        </Button>
      </Space>
    </Card>
  );
};

export default GeneratePost;
