import { useEffect, useState } from "react";
import {} from "@ant-design/icons";
import {
  Button,
  Form,
  notification,
  Select,
  Input,
  Card,
  TimePicker,
  Row,
  Col,
  Switch,
  Space,
  Spin
} from "antd";
import GeneratePost from "./GeneratePost";
import dayjs from "dayjs";

const AIMatchWriter = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [isGettingTeams, setIsGettingTeams] = useState(false);
  const [teams, setTeams] = useState([]);
  const format = "HH:mm";
  const [api, contextHolder] = notification.useNotification();

  const startYear = 2019;
  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: currentYear - startYear + 1 }, (_, i) => {
    const year = startYear + i;
    return {
      value: year,
      label: year.toString()
    };
  });
  const postStatus = [
    { value: "publish", label: "Publish" },
    { value: "draft", label: "Draft" }
  ];

  const openNotificationWithIcon = (type) => {
    api[type]({
      message: "Settings saved!",
      description: "Settings succesfully saved."
    });
  };

  const getSeasonTeam = async (season) => {
    setIsGettingTeams(true);
    const formData = new FormData();
    formData.append("action", "get_season_teams");
    formData.append("season", season);

    const response = await fetch(amw_params.ajax_url, {
      method: "POST",
      headers: {
        "X-WP-Nonce": amw_params.nonce
      },
      body: formData
    });

    if (!response.ok) throw new Error("API request failed");
    const { status, data } = await response.json();

    setIsGettingTeams(false);
    if (status == "success") {
      setTeams(data);
    }
  };

  const onFinish = async (values) => {
    console.log(values);
    // setLoading(true);
    // const formData = new FormData();
    // formData.append("action", "save_settings");
    // formData.append("data", JSON.stringify(values));

    // const response = await fetch(amw_params.ajax_url, {
    //   method: "POST",
    //   headers: {
    //     "X-WP-Nonce": amw_params.nonce
    //   },
    //   body: formData
    // });

    // if (!response.ok) throw new Error("API request failed");
    // const { status, data } = await response.json();
    // setLoading(false);

    // if (status === "success") {
    //   openNotificationWithIcon("success");
    // }
  };

  useEffect(() => {
    form.setFieldsValue({
      amw_enable_auto_generation:
        amw_params?.settings?.amw_enable_auto_generation ?? true,
      amw_open_api_key: amw_params?.settings?.amw_open_api_key ?? [],
      amw_post_status: amw_params?.settings?.amw_post_status ?? "publish",
      amw_time:
        dayjs(amw_params?.settings?.amw_time, format) ?? dayjs("20:00", format),
      amw_user_prompt: amw_params?.settings?.amw_user_prompt ?? [],
      amw_system_prompt: amw_params?.settings?.amw_system_prompt ?? [],
      amw_season: amw_params?.settings?.amw_season ?? currentYear,
      amw_targeted_teams: amw_params?.settings?.amw_targeted_teams ?? []
    });
    setTeams(amw_params?.teams);
  }, []);

  return (
    <>
      <Row gutter={16}>
        <Col xs={24} md={14}>
          <Form
            labelCol={{ span: 6 }}
            wrapperCol={{ span: 16 }}
            form={form}
            name="dynamic_form_complex"
            autoComplete="off"
            onFinish={onFinish}
          >
            {contextHolder}
            <Card
              size="small"
              title="Automation Settings"
              style={{ marginBottom: "40px" }}
            >
              <Form.Item
                name="amw_enable_auto_generation"
                label="Enable Auto Generation"
              >
                <Switch defaultChecked />
              </Form.Item>

              <Form.Item
                label="OpenAI API Key"
                name="amw_open_api_key"
                rules={[
                  { required: true, message: "Please input OpenAI API Key!" }
                ]}
              >
                <Input.Password />
              </Form.Item>

              <Form.Item label="Post Status" name="amw_post_status">
                <Select
                  style={{ width: 200 }}
                  optionFilterProp="label"
                  options={postStatus}
                />
              </Form.Item>

              <Form.Item label="Time">
                <Space direction="vertical" size="small" style={{ gap: 0 }}>
                  <Form.Item
                    name="amw_time"
                    rules={[
                      { required: true, message: "Please select a time!" }
                    ]}
                  >
                    <TimePicker format={format} style={{ width: 200 }} />
                  </Form.Item>
                  <p>
                    The system will check daily fixtures and results. Will auto
                    generate post for fixtures and results on the selected time.
                    Please select a time where no more teams are playing so that
                    the system can include the games in the match writing
                    generation.
                  </p>
                </Space>
              </Form.Item>

              <Form.Item
                label="System Prompt"
                name="amw_system_prompt"
                rules={[
                  { required: true, message: "Please insert system prompt!" }
                ]}
              >
                <Input.TextArea
                  rows={10}
                  maxLength={1000}
                  count={{
                    show: true,
                    max: 1000
                  }}
                />
              </Form.Item>

              <Form.Item
                label="User Prompt"
                name="amw_user_prompt"
                rules={[
                  { required: true, message: "Please insert user prompt!" }
                ]}
                tooltip="{matches} will be replaced with match details."
              >
                <Input.TextArea
                  rows={10}
                  maxLength={1000}
                  count={{
                    show: true,
                    max: 1000
                  }}
                />
              </Form.Item>

              <Form.Item label="Season">
                <Space align="start">
                  <Form.Item name="amw_season" rules={[{ required: true }]}>
                    <Select
                      style={{ width: 200 }}
                      options={years}
                      onChange={getSeasonTeam}
                    />
                  </Form.Item>
                  <p
                    style={{
                      marginTop: 2,
                      display: isGettingTeams ? "block" : "none"
                    }}
                  >
                    <Spin /> Fetching teams for this season.
                  </p>
                </Space>
              </Form.Item>

              <Form.Item label="Targeted Teams" name="amw_targeted_teams">
                <Select
                  mode="multiple"
                  style={{ width: "100%" }}
                  placeholder="Please select teams"
                  optionFilterProp="label"
                  options={teams}
                  disabled={isGettingTeams}
                />
              </Form.Item>

              <Form.Item
                style={{
                  marginTop: "16px",
                  justifyContent: "flex-end",
                  display: "flex"
                }}
              >
                <Button type="primary" htmlType="submit" loading={loading}>
                  Save
                </Button>
              </Form.Item>
            </Card>
          </Form>
        </Col>
        <Col xs={24} md={10}>
          <GeneratePost />
        </Col>
      </Row>
    </>
  );
};
export default AIMatchWriter;
