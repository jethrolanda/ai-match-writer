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
  Switch
} from "antd";
import GeneratePost from "./GeneratePost";
import dayjs from "dayjs";

const AIMatchWriter = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
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

  const openNotificationWithIcon = (type) => {
    api[type]({
      message: "Settings saved!",
      description: "Settings succesfully saved."
    });
  };
  const onFinish = async (values) => {
    setLoading(true);
    const formData = new FormData();
    formData.append("action", "save_settings");
    formData.append("data", JSON.stringify(values));

    const response = await fetch(amw_params.ajax_url, {
      method: "POST",
      headers: {
        "X-WP-Nonce": amw_params.nonce
      },
      body: formData
    });

    if (!response.ok) throw new Error("API request failed");
    const { status, data } = await response.json();
    setLoading(false);

    if (status === "success") {
      openNotificationWithIcon("success");
    }
  };

  useEffect(() => {
    form.setFieldsValue({
      amw_open_api_key: amw_params?.settings?.amw_open_api_key ?? [],
      amw_prompt_template: amw_params?.settings?.amw_prompt_template ?? [],
      amw_frequency: amw_params?.settings?.amw_frequency ?? [],
      amw_time:
        dayjs(amw_params?.settings?.amw_time, format) ?? dayjs("20:00", format),
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
                label="OpenAI API Key"
                name="amw_open_api_key"
                rules={[
                  { required: true, message: "Please input OpenAI API Key!" }
                ]}
              >
                <Input.Password />
              </Form.Item>

              <Form.Item name="amw_enable" label="Enable Auto Generation">
                <Switch defaultChecked />
              </Form.Item>

              <Form.Item
                name="amw_time_range"
                label="Time"
                rules={[{ required: true, message: "Please select a time!" }]}
              >
                <TimePicker format={format} />
                <p>
                  The system will check daily for fixtures and results. Will
                  auto generate post for fixtures and results during the
                  selected time. Please select a time where no more teams are
                  playing so that the system will include the games in the match
                  writing generation.
                </p>
              </Form.Item>

              <Form.Item
                label="User Prompt"
                name="amw_user_prompt"
                rules={[
                  { required: true, message: "Please insert user prompt!" }
                ]}
              >
                <Input.TextArea
                  rows={10}
                  maxLength={500}
                  count={{
                    show: true,
                    max: 500
                  }}
                />
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
                  maxLength={500}
                  count={{
                    show: true,
                    max: 500
                  }}
                />
              </Form.Item>

              <Form.Item
                name="amw_season"
                label="Season"
                rules={[{ required: true }]}
              >
                <Select
                  defaultValue={currentYear}
                  style={{ width: 120 }}
                  options={years}
                />
              </Form.Item>

              <Form.Item
                label="Targeted Teams"
                name="amw_targeted_teams"
                rules={[{ required: true, message: "Please select teams!" }]}
              >
                <Select
                  mode="multiple"
                  style={{ width: "100%" }}
                  placeholder="Please select teams"
                  optionFilterProp="label"
                  options={teams}
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
